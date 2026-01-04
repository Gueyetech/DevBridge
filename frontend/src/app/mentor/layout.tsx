"use client";

import { ReactNode } from "react";
import { useRequireAuth } from "@/hooks/use-auth";
import { useAuthStore } from "@/stores/auth-store";
import { Loader2, LayoutDashboard, Users, Calendar, MessageSquare, FileText } from "lucide-react";
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

const mentorNavItems: NavItem[] = [
  { url: "/mentor", title: "Tableau de bord", icon: LayoutDashboard },
  { url: "/mentor/etudiants", title: "Mes étudiants", icon: Users },
  { url: "/mentor/sessions", title: "Sessions", icon: Calendar },
  { url: "/mentor/messages", title: "Messages", icon: MessageSquare },
  { url: "/mentor/ressources", title: "Ressources", icon: FileText },
];

export default function MentorLayout({ children }: { children: ReactNode }) {
  const { utilisateur, isLoading, isAuthorized } = useRequireAuth(["mentor"]);
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
        appSubtitle="Espace Mentor"
        navItems={mentorNavItems}
      />
      <SidebarInset>
        <header className="flex h-16 shrink-0 items-center justify-between gap-2 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12">
          <div className="flex items-center gap-2 px-4">
            <SidebarTrigger className="-ml-1" />
            <Breadcrumb>
              <BreadcrumbList>
                <BreadcrumbItem>
                  <BreadcrumbPage>Espace Mentor</BreadcrumbPage>
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
