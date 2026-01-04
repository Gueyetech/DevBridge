"use client";

import { ReactNode } from "react";
import { useRequireAuth } from "@/hooks/use-auth";
import { useAuthStore } from "@/stores/auth-store";
import { Loader2, LayoutDashboard, BookOpen, Code, MessageSquare, Trophy, User } from "lucide-react";
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

const etudiantNavItems: NavItem[] = [
  { url: "/etudiant", title: "Tableau de bord", icon: LayoutDashboard },
  { url: "/etudiant/parcours", title: "Mes parcours", icon: BookOpen },
  { url: "/etudiant/projets", title: "Projets", icon: Code },
  { url: "/etudiant/badges", title: "Badges", icon: Trophy },
  { url: "/etudiant/messages", title: "Messages", icon: MessageSquare },
  { url: "/etudiant/profil", title: "Mon profil", icon: User },
];

export default function EtudiantLayout({ children }: { children: ReactNode }) {
  const { utilisateur, isLoading, isAuthorized } = useRequireAuth(["etudiant"]);
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

  if (!isAuthorized) {
    return null;
  }

  return (
    <SidebarProvider>
      <AppSidebar
        appName="DevBridge"
        appSubtitle="Espace Étudiant"
        navItems={etudiantNavItems}
      />
      <SidebarInset>
        <header className="flex h-16 shrink-0 items-center justify-between gap-2 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12">
          <div className="flex items-center gap-2 px-4">
            <SidebarTrigger className="-ml-1" />
            <Breadcrumb>
              <BreadcrumbList>
                <BreadcrumbItem>
                  <BreadcrumbPage>Espace Étudiant</BreadcrumbPage>
                </BreadcrumbItem>
              </BreadcrumbList>
            </Breadcrumb>
          </div>
          <div className="px-4">
            {utilisateur && (
              <HeaderUser
                user={{
                  name: `${utilisateur.prenom} ${utilisateur.nom}`,
                  email: utilisateur.email,
                  avatar: utilisateur.avatar,
                }}
                onLogout={handleLogout}
              />
            )}
          </div>
        </header>
        <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
          {children}
        </div>
      </SidebarInset>
    </SidebarProvider>
  );
}
