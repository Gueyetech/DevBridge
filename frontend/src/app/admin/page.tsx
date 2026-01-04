"use client";

import React, { useState, useEffect } from "react";
import { apiClient } from "@/lib/api";
import {
  Loader2,
  Users,
  BookOpen,
  Award,
  TrendingUp,
  FolderKanban,
  UserCog,
  Trophy,
  Activity,
  AlertTriangle,
  Clock,
  ArrowUpRight,
} from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Progress } from "@/components/ui/progress";
import { Separator } from "@/components/ui/separator";

interface TableauDeBordData {
  statistiques: {
    utilisateurs: {
      total: number;
      etudiants: number;
      mentors: number;
      administrateurs: number;
      actifs: number;
      nouveaux_cette_semaine: number;
    };
    apprentissage: {
      parcours_total: number;
      parcours_publies: number;
      modules_total: number;
      lecons_total: number;
      completions_cette_semaine: number;
    };
    projets: {
      total: number;
      en_cours: number;
      termines: number;
      nouveaux_cette_semaine: number;
    };
    mentorat: {
      total: number;
      actifs: number;
      en_attente: number;
    };
    gamification: {
      points_distribues: number;
      badges_attribues: number;
      defis_actifs: number;
    };
    performance: {
      taux_completion_moyen: number;
      temps_moyen_session: number;
      note_moyenne: number;
    };
  };
  donnees_recentes: {
    nouvelles_inscriptions: Array<{
      id: string;
      prenom: string;
      nom: string;
      email: string;
      avatar: string | null;
      created_at: string;
    }>;
    projets_recents: Array<{
      id: string;
      nom: string;
      statut: string;
      createur: { prenom: string; nom: string };
      created_at: string;
    }>;
    parcours_populaires: Array<{
      id: string;
      titre: string;
      utilisateurs_inscrits_count: number;
    }>;
  };
  alertes: Array<{
    type: string;
    message: string;
    priorite: string;
  }>;
}

interface ApiResponse {
  success: boolean;
  data: TableauDeBordData;
}

export default function AdminDashboard() {
  const [data, setData] = useState<TableauDeBordData | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const response = await apiClient.get<ApiResponse>("/v1/admin/tableau-de-bord");
        setData(response.data);
      } catch (error) {
        console.error("Erreur lors du chargement:", error);
      } finally {
        setIsLoading(false);
      }
    };
    fetchData();
  }, []);

  const getInitials = (prenom: string, nom: string) => {
    return `${prenom.charAt(0)}${nom.charAt(0)}`.toUpperCase();
  };

  const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString("fr-FR", {
      day: "numeric",
      month: "short",
    });
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <Loader2 className="h-8 w-8 animate-spin" />
      </div>
    );
  }

  const stats = data?.statistiques;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-3xl font-bold">Tableau de bord</h1>
        <p className="text-muted-foreground">Vue d&apos;ensemble de la plateforme DevBridge</p>
      </div>

      {/* Alertes */}
      {data?.alertes && data.alertes.length > 0 && (
        <Card className="border-amber-500/50 bg-amber-500/5">
          <CardContent className="py-3">
            <div className="flex items-center gap-3">
              <AlertTriangle className="h-5 w-5 text-amber-500" />
              <div>
                <p className="font-medium text-amber-700">Alertes système</p>
                <p className="text-sm text-amber-600">{data.alertes[0]?.message}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Statistiques principales */}
      <div className="grid gap-3 grid-cols-2 lg:grid-cols-4">
        <Card className="border-l-4 border-l-blue-500 py-3">
          <CardContent className="flex items-center gap-3 p-0 px-4">
            <div className="p-2 rounded-full bg-blue-500/10">
              <Users className="h-4 w-4 text-blue-500" />
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-2xl font-bold">{stats?.utilisateurs?.total || 0}</p>
              <p className="text-xs text-muted-foreground">Utilisateurs</p>
            </div>
            {stats?.utilisateurs?.nouveaux_cette_semaine ? (
              <Badge variant="outline" className="text-green-600 border-green-500/50 bg-green-500/10 text-xs">
                <ArrowUpRight className="h-3 w-3 mr-0.5" />
                +{stats.utilisateurs.nouveaux_cette_semaine}
              </Badge>
            ) : null}
          </CardContent>
        </Card>

        <Card className="border-l-4 border-l-purple-500 py-3">
          <CardContent className="flex items-center gap-3 p-0 px-4">
            <div className="p-2 rounded-full bg-purple-500/10">
              <BookOpen className="h-4 w-4 text-purple-500" />
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-2xl font-bold">{stats?.apprentissage?.parcours_publies || 0}</p>
              <p className="text-xs text-muted-foreground">Parcours actifs</p>
            </div>
          </CardContent>
        </Card>

        <Card className="border-l-4 border-l-green-500 py-3">
          <CardContent className="flex items-center gap-3 p-0 px-4">
            <div className="p-2 rounded-full bg-green-500/10">
              <FolderKanban className="h-4 w-4 text-green-500" />
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-2xl font-bold">{stats?.projets?.en_cours || 0}</p>
              <p className="text-xs text-muted-foreground">Projets en cours</p>
            </div>
            {stats?.projets?.nouveaux_cette_semaine ? (
              <Badge variant="outline" className="text-green-600 border-green-500/50 bg-green-500/10 text-xs">
                <ArrowUpRight className="h-3 w-3 mr-0.5" />
                +{stats.projets.nouveaux_cette_semaine}
              </Badge>
            ) : null}
          </CardContent>
        </Card>

        <Card className="border-l-4 border-l-amber-500 py-3">
          <CardContent className="flex items-center gap-3 p-0 px-4">
            <div className="p-2 rounded-full bg-amber-500/10">
              <UserCog className="h-4 w-4 text-amber-500" />
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-2xl font-bold">{stats?.mentorat?.actifs || 0}</p>
              <p className="text-xs text-muted-foreground">Mentorats actifs</p>
            </div>
            {stats?.mentorat?.en_attente ? (
              <Badge variant="outline" className="text-amber-600 border-amber-500/50 bg-amber-500/10 text-xs">
                {stats.mentorat.en_attente} en attente
              </Badge>
            ) : null}
          </CardContent>
        </Card>
      </div>

      {/* Stats secondaires */}
      <div className="grid gap-3 grid-cols-2 md:grid-cols-4">
        <Card className="py-2">
          <CardContent className="flex items-center justify-between p-0 px-4">
            <div>
              <p className="text-lg font-bold">{stats?.gamification?.badges_attribues || 0}</p>
              <p className="text-xs text-muted-foreground">Badges attribués</p>
            </div>
            <Award className="h-5 w-5 text-muted-foreground/50" />
          </CardContent>
        </Card>
        <Card className="py-2">
          <CardContent className="flex items-center justify-between p-0 px-4">
            <div>
              <p className="text-lg font-bold">{stats?.gamification?.defis_actifs || 0}</p>
              <p className="text-xs text-muted-foreground">Défis actifs</p>
            </div>
            <Trophy className="h-5 w-5 text-muted-foreground/50" />
          </CardContent>
        </Card>
        <Card className="py-2">
          <CardContent className="flex items-center justify-between p-0 px-4">
            <div>
              <p className="text-lg font-bold">{stats?.performance?.taux_completion_moyen || 0}%</p>
              <p className="text-xs text-muted-foreground">Taux complétion</p>
            </div>
            <TrendingUp className="h-5 w-5 text-muted-foreground/50" />
          </CardContent>
        </Card>
        <Card className="py-2">
          <CardContent className="flex items-center justify-between p-0 px-4">
            <div>
              <p className="text-lg font-bold">{stats?.utilisateurs?.actifs || 0}</p>
              <p className="text-xs text-muted-foreground">Utilisateurs actifs</p>
            </div>
            <Activity className="h-5 w-5 text-muted-foreground/50" />
          </CardContent>
        </Card>
      </div>

      {/* Contenu en 2 colonnes */}
      <div className="grid gap-4 lg:grid-cols-2">
        {/* Nouvelles inscriptions */}
        <Card>
          <CardHeader className="py-4">
            <div className="flex items-center justify-between">
              <div>
                <CardTitle className="text-base">Nouvelles inscriptions</CardTitle>
                <CardDescription>Les derniers utilisateurs inscrits</CardDescription>
              </div>
              <Users className="h-4 w-4 text-muted-foreground" />
            </div>
          </CardHeader>
          <Separator />
          <CardContent className="p-0">
            <div className="divide-y">
              {data?.donnees_recentes?.nouvelles_inscriptions?.slice(0, 5).map((user) => (
                <div key={user.id} className="flex items-center gap-3 px-4 py-3 hover:bg-muted/30 transition-colors">
                  <Avatar className="h-9 w-9">
                    <AvatarImage src={user.avatar || undefined} />
                    <AvatarFallback className="bg-primary/10 text-primary text-sm">
                      {getInitials(user.prenom, user.nom)}
                    </AvatarFallback>
                  </Avatar>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium truncate">{user.prenom} {user.nom}</p>
                    <p className="text-xs text-muted-foreground truncate">{user.email}</p>
                  </div>
                  <span className="text-xs text-muted-foreground">{formatDate(user.created_at)}</span>
                </div>
              ))}
              {(!data?.donnees_recentes?.nouvelles_inscriptions || data.donnees_recentes.nouvelles_inscriptions.length === 0) && (
                <div className="px-4 py-8 text-center text-muted-foreground text-sm">
                  Aucune inscription récente
                </div>
              )}
            </div>
          </CardContent>
        </Card>

        {/* Parcours populaires */}
        <Card>
          <CardHeader className="py-4">
            <div className="flex items-center justify-between">
              <div>
                <CardTitle className="text-base">Parcours populaires</CardTitle>
                <CardDescription>Les parcours les plus suivis</CardDescription>
              </div>
              <BookOpen className="h-4 w-4 text-muted-foreground" />
            </div>
          </CardHeader>
          <Separator />
          <CardContent className="p-0">
            <div className="divide-y">
              {data?.donnees_recentes?.parcours_populaires?.slice(0, 5).map((parcours, index) => (
                <div key={parcours.id} className="flex items-center gap-3 px-4 py-3 hover:bg-muted/30 transition-colors">
                  <span className={`flex items-center justify-center h-7 w-7 rounded-full text-xs font-bold ${
                    index === 0 ? "bg-amber-500/20 text-amber-600" :
                    index === 1 ? "bg-gray-300/30 text-gray-600" :
                    index === 2 ? "bg-orange-500/20 text-orange-600" :
                    "bg-muted text-muted-foreground"
                  }`}>
                    {index + 1}
                  </span>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium truncate">{parcours.titre}</p>
                    <div className="flex items-center gap-2 mt-1">
                      <Progress value={Math.min((parcours.utilisateurs_inscrits_count / 100) * 100, 100)} className="h-1.5 flex-1" />
                      <span className="text-xs text-muted-foreground whitespace-nowrap">
                        {parcours.utilisateurs_inscrits_count} inscrits
                      </span>
                    </div>
                  </div>
                </div>
              ))}
              {(!data?.donnees_recentes?.parcours_populaires || data.donnees_recentes.parcours_populaires.length === 0) && (
                <div className="px-4 py-8 text-center text-muted-foreground text-sm">
                  Aucun parcours disponible
                </div>
              )}
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Projets récents */}
      <Card>
        <CardHeader className="py-4">
          <div className="flex items-center justify-between">
            <div>
              <CardTitle className="text-base">Projets récents</CardTitle>
              <CardDescription>Derniers projets créés sur la plateforme</CardDescription>
            </div>
            <FolderKanban className="h-4 w-4 text-muted-foreground" />
          </div>
        </CardHeader>
        <Separator />
        <CardContent className="p-0">
          <div className="grid md:grid-cols-2 lg:grid-cols-3 divide-y md:divide-y-0 md:divide-x">
            {data?.donnees_recentes?.projets_recents?.slice(0, 3).map((projet) => (
              <div key={projet.id} className="px-4 py-4 hover:bg-muted/30 transition-colors">
                <div className="flex items-start justify-between gap-2">
                  <p className="font-medium text-sm truncate">{projet.nom}</p>
                  <Badge variant="outline" className={`text-xs shrink-0 ${
                    projet.statut === "en_cours" ? "border-blue-500/50 bg-blue-500/10 text-blue-600" :
                    projet.statut === "termine" ? "border-green-500/50 bg-green-500/10 text-green-600" :
                    "border-gray-500/50 bg-gray-500/10 text-gray-600"
                  }`}>
                    {projet.statut === "en_cours" ? "En cours" : 
                     projet.statut === "termine" ? "Terminé" : projet.statut}
                  </Badge>
                </div>
                <p className="text-xs text-muted-foreground mt-1">
                  par {projet.createur?.prenom} {projet.createur?.nom}
                </p>
                <div className="flex items-center gap-1 mt-2 text-xs text-muted-foreground">
                  <Clock className="h-3 w-3" />
                  {formatDate(projet.created_at)}
                </div>
              </div>
            ))}
            {(!data?.donnees_recentes?.projets_recents || data.donnees_recentes.projets_recents.length === 0) && (
              <div className="col-span-3 px-4 py-8 text-center text-muted-foreground text-sm">
                Aucun projet récent
              </div>
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
