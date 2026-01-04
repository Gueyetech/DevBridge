export type RoleUtilisateur = "etudiant" | "mentor" | "administrateur";

export interface Utilisateur {
  id: number;
  nom: string;
  prenom: string;
  email: string;
  role: RoleUtilisateur;
  avatar?: string;
  date_inscription?: string;
}

export interface LoginRequest {
  email: string;
  mot_de_passe: string;
}

export interface RegisterRequest {
  nom: string;
  prenom: string;
  email: string;
  mot_de_passe: string;
  mot_de_passe_confirmation: string;
  role?: RoleUtilisateur;
}

export interface AuthResponse {
  message: string;
  utilisateur: Utilisateur;
  token: string;
}
