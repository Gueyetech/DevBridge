"use client";

import { ReactNode } from "react";
import { useAuthStore } from "@/stores/auth-store";
import { Loader2, MessageSquare, Mail, ArrowLeft } from "lucide-react";
import Link from "next/link";
import { Button } from "@/components/ui/button";

export default function CommunLayout({ children }: { children: ReactNode }) {
  const { utilisateur, isLoading, isAuthenticated } = useAuthStore();

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <Loader2 className="h-8 w-8 animate-spin" />
      </div>
    );
  }

  if (!isAuthenticated || !utilisateur) {
    if (typeof window !== "undefined") window.location.href = "/connexion";
    return null;
  }

  const role = utilisateur.role || "etudiant";
  const dashboardUrl = `/${role}`;

  return (
    <div className="min-h-screen bg-background">
      <header className="border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
        <div className="container flex h-14 items-center gap-4 px-4">
          <Link href={dashboardUrl}>
            <Button variant="ghost" size="sm"><ArrowLeft className="h-4 w-4 mr-2" /> Retour</Button>
          </Link>
          <nav className="flex gap-2">
            <Link href="/forum"><Button variant="ghost" size="sm"><MessageSquare className="h-4 w-4 mr-1" /> Forum</Button></Link>
            <Link href="/messagerie"><Button variant="ghost" size="sm"><Mail className="h-4 w-4 mr-1" /> Messagerie</Button></Link>
          </nav>
          <div className="ml-auto text-sm text-muted-foreground">{utilisateur.prenom} {utilisateur.nom}</div>
        </div>
      </header>
      <main className="container py-6 px-4">{children}</main>
    </div>
  );
}
