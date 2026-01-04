"use client";

import { useEffect } from "react";
import { usePathname } from "next/navigation";
import { useAuthStore } from "@/stores/auth-store";
import type { RoleUtilisateur } from "@/types";

// Mapper les rôles vers les routes de dashboard
export function getRoleDashboardPath(role: RoleUtilisateur): string {
  switch (role) {
    case "administrateur":
      return "/admin";
    case "mentor":
      return "/mentor";
    case "etudiant":
    default:
      return "/etudiant";
  }
}

export function useAuth() {
  const {
    utilisateur,
    isAuthenticated,
    isLoading,
    error,
    login,
    register,
    logout,
    clearError,
  } = useAuthStore();

  return {
    utilisateur,
    isAuthenticated,
    isLoading,
    error,
    login,
    register,
    logout,
    clearError,
  };
}

export function useRequireAuth(allowedRoles?: RoleUtilisateur[]) {
  const pathname = usePathname();
  const { utilisateur, isAuthenticated, isLoading, _hasHydrated } = useAuthStore();

  useEffect(() => {
    if (!_hasHydrated) return;

    if (!isLoading && !isAuthenticated) {
      window.location.href = `/connexion?redirect=${encodeURIComponent(pathname)}`;
      return;
    }

    if (
      !isLoading &&
      isAuthenticated &&
      utilisateur &&
      allowedRoles &&
      !allowedRoles.includes(utilisateur.role)
    ) {
      window.location.href = getRoleDashboardPath(utilisateur.role);
    }
  }, [isAuthenticated, isLoading, utilisateur, allowedRoles, _hasHydrated, pathname]);

  return {
    utilisateur,
    isAuthenticated,
    isLoading: isLoading || !_hasHydrated,
    isAuthorized:
      !allowedRoles ||
      (utilisateur && allowedRoles.includes(utilisateur.role)),
  };
}

export function useRedirectIfAuthenticated() {
  const { utilisateur, isAuthenticated, isLoading, _hasHydrated } = useAuthStore();

  useEffect(() => {
    if (!_hasHydrated) return;

    if (!isLoading && isAuthenticated && utilisateur) {
      window.location.href = getRoleDashboardPath(utilisateur.role);
    }
  }, [isAuthenticated, isLoading, utilisateur, _hasHydrated]);

  return { isLoading: isLoading || !_hasHydrated, isAuthenticated };
}
