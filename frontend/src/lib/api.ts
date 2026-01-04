const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api";
const BACKEND_URL = API_URL.replace("/api", "");

class ApiClient {
  private baseURL: string;
  private backendURL: string;
  private token: string | null = null;

  constructor() {
    this.baseURL = API_URL;
    this.backendURL = BACKEND_URL;
    if (typeof window !== "undefined") {
      this.token = localStorage.getItem("auth_token");
    }
  }

  setToken(token: string | null) {
    this.token = token;
    if (typeof window !== "undefined") {
      if (token) {
        localStorage.setItem("auth_token", token);
      } else {
        localStorage.removeItem("auth_token");
      }
    }
  }

  getToken(): string | null {
    return this.token;
  }

  // Récupérer le cookie CSRF de Laravel Sanctum
  async getCsrfCookie(): Promise<void> {
    await fetch(`${this.backendURL}/sanctum/csrf-cookie`, {
      method: "GET",
      credentials: "include",
    });
  }

  // Récupérer le token XSRF depuis les cookies
  private getXsrfToken(): string | null {
    if (typeof document === "undefined") return null;
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    if (match) {
      return decodeURIComponent(match[1]);
    }
    return null;
  }

  async request<T>(endpoint: string, options: RequestInit = {}): Promise<T> {
    const url = `${this.baseURL}${endpoint}`;
    
    const headers: HeadersInit = {
      "Content-Type": "application/json",
      "Accept": "application/json",
      ...options.headers,
    };

    // Ajouter le token XSRF si disponible
    const xsrfToken = this.getXsrfToken();
    if (xsrfToken) {
      (headers as Record<string, string>)["X-XSRF-TOKEN"] = xsrfToken;
    }

    // Ajouter le Bearer token si disponible
    if (this.token) {
      (headers as Record<string, string>)["Authorization"] = `Bearer ${this.token}`;
    }

    const response = await fetch(url, {
      ...options,
      headers,
      credentials: "include",
    });

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      throw new Error(errorData.message || `Erreur ${response.status}`);
    }

    return response.json();
  }

  async get<T>(endpoint: string): Promise<T> {
    return this.request<T>(endpoint, { method: "GET" });
  }

  async post<T>(endpoint: string, data?: unknown): Promise<T> {
    return this.request<T>(endpoint, {
      method: "POST",
      body: data ? JSON.stringify(data) : undefined,
    });
  }

  async put<T>(endpoint: string, data?: unknown): Promise<T> {
    return this.request<T>(endpoint, {
      method: "PUT",
      body: data ? JSON.stringify(data) : undefined,
    });
  }

  async delete<T>(endpoint: string): Promise<T> {
    return this.request<T>(endpoint, { method: "DELETE" });
  }

  // Méthode pour uploader des fichiers (FormData)
  async upload<T>(endpoint: string, formData: FormData): Promise<T> {
    const url = `${this.baseURL}${endpoint}`;
    
    const headers: HeadersInit = {
      "Accept": "application/json",
    };

    // Ajouter le token XSRF si disponible
    const xsrfToken = this.getXsrfToken();
    if (xsrfToken) {
      (headers as Record<string, string>)["X-XSRF-TOKEN"] = xsrfToken;
    }

    // Ajouter le Bearer token si disponible
    if (this.token) {
      (headers as Record<string, string>)["Authorization"] = `Bearer ${this.token}`;
    }

    const response = await fetch(url, {
      method: "POST",
      headers,
      body: formData,
      credentials: "include",
    });

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      throw new Error(errorData.message || `Erreur ${response.status}`);
    }

    return response.json();
  }
}

export const apiClient = new ApiClient();
