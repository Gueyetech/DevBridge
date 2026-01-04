import { create } from "zustand";
import { persist } from "zustand/middleware";
import type { Utilisateur, LoginRequest, RegisterRequest, AuthResponse } from "@/types";
import { apiClient } from "@/lib/api";

interface AuthState {
  utilisateur: Utilisateur | null;
  token: string | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  error: string | null;
  _hasHydrated: boolean;

  // Actions
  login: (data: LoginRequest) => Promise<AuthResponse | undefined>;
  register: (data: RegisterRequest) => Promise<AuthResponse | undefined>;
  logout: () => Promise<void>;
  clearError: () => void;
  setHasHydrated: (state: boolean) => void;
  setUtilisateur: (utilisateur: Utilisateur) => void;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      utilisateur: null,
      token: null,
      isLoading: false,
      isAuthenticated: false,
      error: null,
      _hasHydrated: false,

      setHasHydrated: (state: boolean) => {
        set({ _hasHydrated: state });
      },

      login: async (data: LoginRequest) => {
        set({ isLoading: true, error: null });
        try {
          // Récupérer le cookie CSRF avant la connexion
          await apiClient.getCsrfCookie();
          const response = await apiClient.post<AuthResponse>("/auth/connexion", data);
          apiClient.setToken(response.token);
          set({
            utilisateur: response.utilisateur,
            token: response.token,
            isAuthenticated: true,
            isLoading: false,
          });
          return response;
        } catch (error: unknown) {
          const message = error instanceof Error ? error.message : "Erreur de connexion";
          set({ error: message, isLoading: false });
          throw error;
        }
      },

      register: async (data: RegisterRequest) => {
        set({ isLoading: true, error: null });
        try {
          // Récupérer le cookie CSRF avant l'inscription
          await apiClient.getCsrfCookie();
          const response = await apiClient.post<AuthResponse>("/auth/inscription", data);
          apiClient.setToken(response.token);
          set({
            utilisateur: response.utilisateur,
            token: response.token,
            isAuthenticated: true,
            isLoading: false,
          });
          return response;
        } catch (error: unknown) {
          const message = error instanceof Error ? error.message : "Erreur d'inscription";
          set({ error: message, isLoading: false });
          throw error;
        }
      },

      logout: async () => {
        set({ isLoading: true });
        try {
          await apiClient.post("/auth/deconnexion");
        } catch {
          // Ignorer les erreurs de déconnexion
        } finally {
          apiClient.setToken(null);
          set({
            utilisateur: null,
            token: null,
            isAuthenticated: false,
            isLoading: false,
          });
        }
      },

      clearError: () => {
        set({ error: null });
      },

      setUtilisateur: (utilisateur: Utilisateur) => {
        set({ utilisateur });
      },
    }),
    {
      name: "auth-storage",
      partialize: (state) => ({
        token: state.token,
        utilisateur: state.utilisateur,
        isAuthenticated: state.isAuthenticated,
      }),
      onRehydrateStorage: () => (state, error) => {
        if (error) {
          console.error("Erreur d'hydratation:", error);
          return;
        }
        if (state?.token) {
          apiClient.setToken(state.token);
        }
        if (state) {
          state.setHasHydrated(true);
        }
      },
    }
  )
);
