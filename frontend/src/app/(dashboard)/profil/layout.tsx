"use client";

import { ReactNode } from "react";
import { useRequireAuth } from "@/hooks/use-auth";
import { useAuthStore } from "@/stores/auth-store";
import { Loader2, LayoutDashboard, BookOpen, Code, MessageSquare, Trophy, User, Users, Calendar, FileText, BarChart, Settings } from "lucide-react";
import { AppSidebar, NavItem } from "@/components/app-sidebar";
import { HeaderUser } from "@/components/header-user";
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbList,
  BreadcrumbPage,
} from "@/components/ui/breadcrumb";
import { Separator } from "@/components/ui/separator";
import {
  SidebarInset,
  SidebarProvider,
  SidebarTrigger,
} from "@/components/ui/sidebar";

// Nav items selon le rôle
const navItemsByRole: Record<string, NavItem[]> = {
  etudiant: [
    { url: "/etudiant", title: "Tableau de bord", icon: LayoutDashboard },
    { url: "/etudiant/parcours", title: "Mes parcours", icon: BookOpen },
    { url: "/etudiant/projets", title: "Projets", icon: Code },
    { url: "/etudiant/badges", title: "Badges", icon: Trophy },
    { url: "/etudiant/messages", title: "Messages", icon: MessageSquare },
    { url: "/profil", title: "Mon profil", icon: User },
  ],
  mentor: [
    { url: "/mentor", title: "Tableau de bord", icon: LayoutDashboard },
    { url: "/mentor/etudiants", title: "Mes étudiants", icon: Users },
    { url: "/mentor/sessions", title: "Sessions", icon: Calendar },
    { url: "/mentor/messages", title: "Messages", icon: MessageSquare },
    { url: "/mentor/ressources", title: "Ressources", icon: FileText },
    { url: "/profil", title: "Mon profil", icon: User },
  ],
  administrateur: [
    { url: "/admin", title: "Tableau de bord", icon: LayoutDashboard },
    { url: "/admin/utilisateurs", title: "Utilisateurs", icon: Users },
    { url: "/admin/parcours", title: "Parcours", icon: BookOpen },
    { url: "/admin/statistiques", title: "Statistiques", icon: BarChart },
    { url: "/admin/parametres", title: "Paramètres", icon: Settings },
    { url: "/profil", title: "Mon profil", icon: User },
  ],
};

const subtitleByRole: Record<string, string> = {
  etudiant: "Espace Étudiant",
  mentor: "Espace Mentor",
  administrateur: "Administration",
};

export default function ProfilLayout({ children }: { children: ReactNode }) {
  const { utilisateur, isLoading, isAuthorized } = useRequireAuth();
  const logout = useAuthStore((state) => state.logout);

  const handleLogout = async () => {
    await logout();
    window.location.href = "/connexion";
  };

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <Loader2 className="h-8 w-8 animate-spin" />
      </div>
    );
  }

  if (!isAuthorized || !utilisateur) {
    return null;
  }

  const navItems = navItemsByRole[utilisateur.role] || navItemsByRole.etudiant;
  const subtitle = subtitleByRole[utilisateur.role] || "DevBridge";

  return (
    <SidebarProvider>
      <AppSidebar
        appName="DevBridge"
        appSubtitle={subtitle}
        navItems={navItems}
      />
      <SidebarInset>
        <header className="flex h-16 shrink-0 items-center justify-between gap-2 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12">
          <div className="flex items-center gap-2 px-4">
            <SidebarTrigger className="-ml-1" />
            <Breadcrumb>
              <BreadcrumbList>
                <BreadcrumbItem>
                  <BreadcrumbPage>Mon Profil</BreadcrumbPage>
                </BreadcrumbItem>
              </BreadcrumbList>
            </Breadcrumb>
          </div>
          <div className="px-4">
            <HeaderUser
              user={{
                name: `${utilisateur.prenom} ${utilisateur.nom}`,
                email: utilisateur.email,
                avatar: utilisateur.avatar,
              }}
              onLogout={handleLogout}
            />
          </div>
        </header>
        <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
          {children}
        </div>
      </SidebarInset>
    </SidebarProvider>
  );
}
