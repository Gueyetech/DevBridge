"use client";

import React from "react";
import {
  BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, PieChart, Pie, Cell, LineChart, Line, Legend
} from "recharts";
import {
  Users,
  Calendar,
  Award,
  Clock,
  TrendingUp,
  Loader,
  Loader2,
} from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Progress } from "@/components/ui/progress";
import { Separator } from "@/components/ui/separator";

import { useEffect, useState } from "react";

type MentorDashboardApi = {
  statistiques: {
    total_etudiants?: number;
    nouveaux_etudiants: number;
    sessions_terminees: number;
    temps_total_mentorat: number;
    temps_moyen_session: number;
    competences_validees: number;
    feedback_donnes: number;
    [key: string]: any;
  };
  sessions_par_mois: { mois: string; sessions: number }[];
  competences_par_type: { name: string; value: number }[];
  progression_moyenne_par_mois: { mois: string; progression: number }[];
  etudiants?: any[];
  prochaines_sessions?: any[];
};
import { apiClient } from "@/lib/api";



function getInitials(prenom: string, nom: string) {
  return `${prenom.charAt(0)}${nom.charAt(0)}`.toUpperCase();
}

export default function MentorDashboardPage() {
  const [data, setData] = useState<MentorDashboardApi | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    apiClient.get<any>("/v1/mentor/tableau-de-bord")
      .then((res) => {
        setData(res.data);
        setIsLoading(false);
      })
      .catch((err) => {
        console.error("Erreur API mentor:", err);
        setIsLoading(false);
      });
  }, []);

  if (isLoading) {
 return (
      <div className="flex items-center justify-center min-h-[400px]">
        <Loader2 className="h-8 w-8 animate-spin" />
      </div>
    );  }

  if (!data || !data.statistiques) {
    return <div className="text-center py-12">Aucune donnée à afficher.</div>;
  }

  // Calcul de la progression moyenne des étudiants
  const progressionMoyenne = data.etudiants && data.etudiants.length > 0
    ? Math.round(data.etudiants.reduce((acc: number, e: any) => acc + (e.progression || 0), 0) / data.etudiants.length)
    : 0;

  // Prochaines sessions programmées (exemple, à adapter selon la structure API)
  const prochainesSessions = data.prochaines_sessions || [];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Tableau de bord Mentor</h1>
        <p className="text-muted-foreground">Vue d'ensemble de votre activité de mentorat</p>
      </div>
      {/* Indicateurs chiffrés améliorés */}
      <div className="grid gap-3 grid-cols-2 md:grid-cols-4">
        <Card className="border-l-4 border-l-blue-500 py-3">
          <CardContent className="flex items-center gap-3 p-0 px-4">
            <div className="p-2 rounded-full bg-blue-500/10">
              <Users className="h-4 w-4 text-blue-500" />
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-2xl font-bold">{data.statistiques.nouveaux_etudiants}</p>
              <p className="text-xs text-muted-foreground">Nouveaux étudiants mentorés</p>
            </div>
            {data.statistiques.nouveaux_etudiants > 0 && (
              <span className="text-green-600 border-green-500/50 bg-green-500/10 text-xs px-2 py-1 rounded font-semibold ml-2">+{data.statistiques.nouveaux_etudiants}</span>
            )}
          </CardContent>
        </Card>
        <Card className="border-l-4 border-l-cyan-500 py-3">
          <CardContent className="flex items-center gap-3 p-0 px-4">
            <div className="p-2 rounded-full bg-cyan-500/10">
              <Users className="h-4 w-4 text-cyan-500" />
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-2xl font-bold">{data.statistiques.total_etudiants ?? 0}</p>
              <p className="text-xs text-muted-foreground">Étudiants mentorés</p>
            </div>
          </CardContent>
        </Card>
        <Card className="border-l-4 border-l-green-500 py-3">
          <CardContent className="flex items-center gap-3 p-0 px-4">
            <div className="p-2 rounded-full bg-green-500/10">
              <Calendar className="h-4 w-4 text-green-500" />
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-2xl font-bold">{data.statistiques.sessions_terminees}</p>
              <p className="text-xs text-muted-foreground">Sessions terminées</p>
            </div>
          </CardContent>
        </Card>
        <Card className="border-l-4 border-l-purple-500 py-3">
          <CardContent className="flex items-center gap-3 p-0 px-4">
            <div className="p-2 rounded-full bg-purple-500/10">
              <Clock className="h-4 w-4 text-purple-500" />
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-2xl font-bold">{data.statistiques.temps_total_mentorat} min</p>
              <p className="text-xs text-muted-foreground">Temps total mentorat</p>
            </div>
          </CardContent>
        </Card>
        <Card className="border-l-4 border-l-amber-500 py-3">
          <CardContent className="flex items-center gap-3 p-0 px-4">
            <div className="p-2 rounded-full bg-amber-500/10">
              <Award className="h-4 w-4 text-amber-500" />
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-2xl font-bold">{data.statistiques.temps_moyen_session} min</p>
              <p className="text-xs text-muted-foreground">Temps moyen/session</p>
            </div>
          </CardContent>
        </Card>
        <Card className="border-l-4 border-l-fuchsia-500 py-3">
          <CardContent className="flex items-center gap-3 p-0 px-4">
            <div className="p-2 rounded-full bg-fuchsia-500/10">
              <TrendingUp className="h-4 w-4 text-fuchsia-500" />
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-2xl font-bold">{progressionMoyenne}%</p>
              <p className="text-xs text-muted-foreground">Progression moyenne étudiants</p>
            </div>
          </CardContent>
        </Card>
        {/* Ajout d'autres indicateurs */}
        <Card className="border-l-4 border-l-pink-500 py-3">
          <CardContent className="flex items-center gap-3 p-0 px-4">
            <div className="p-2 rounded-full bg-pink-500/10">
              <Award className="h-4 w-4 text-pink-500" />
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-2xl font-bold">{data.statistiques.competences_validees}</p>
              <p className="text-xs text-muted-foreground">Compétences validées</p>
            </div>
          </CardContent>
        </Card>
        <Card className="border-l-4 border-l-orange-500 py-3">
          <CardContent className="flex items-center gap-3 p-0 px-4">
            <div className="p-2 rounded-full bg-orange-500/10">
              <Award className="h-4 w-4 text-orange-500" />
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-2xl font-bold">{data.statistiques.feedback_donnes}</p>
              <p className="text-xs text-muted-foreground">Feedbacks donnés</p>
            </div>
          </CardContent>
        </Card>
      </div>
      <Separator className="my-8" />
      {/* Graphiques mentor avec Recharts et vraies données API */}
      <div className="mt-8 grid gap-8 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
        {/* BarChart sessions/mois */}
        <Card>
          <CardHeader>
            <CardTitle>Évolution des sessions de mentorat</CardTitle>
            <CardDescription>Sessions terminées par mois</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="h-64">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={data.sessions_par_mois as any[] || []}>
                  <XAxis dataKey="mois" />
                  <YAxis />
                  <Tooltip />
                  <Bar dataKey="sessions" fill="#6366f1" />
                </BarChart>
              </ResponsiveContainer>
            </div>
          </CardContent>
        </Card>
        {/* PieChart compétences validées */}
        <Card>
          <CardHeader>
            <CardTitle>Répartition des compétences validées</CardTitle>
            <CardDescription>Par type de compétence</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="h-64">
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie data={data.competences_par_type as any[] || []} dataKey="value" nameKey="name" cx="50%" cy="50%" outerRadius={60} label>
                    {(data.competences_par_type as any[] || []).map((entry: any, idx: number) => (
                      <Cell key={`cell-${idx}`} fill={["#6366f1", "#22d3ee", "#f59e42", "#10b981", "#ef4444"][idx % 5]} />
                    ))}
                  </Pie>
                  <Tooltip />
                </PieChart>
              </ResponsiveContainer>
            </div>
          </CardContent>
        </Card>
        {/* LineChart progression moyenne */}
        <Card>
          <CardHeader>
            <CardTitle>Progression moyenne des étudiants</CardTitle>
            <CardDescription>Évolution sur la période</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="h-64">
              <ResponsiveContainer width="100%" height="100%">
                <LineChart data={data.progression_moyenne_par_mois as any[] || []}>
                  <XAxis dataKey="mois" />
                  <YAxis />
                  <Tooltip />
                  <Legend />
                  <Line type="monotone" dataKey="progression" stroke="#6366f1" strokeWidth={2} />
                </LineChart>
              </ResponsiveContainer>
            </div>
          </CardContent>
        </Card>
      </div>
      {/* Prochaines sessions programmées */}
      {prochainesSessions.length > 0 && (
        <div className="mt-8">
          <h2 className="text-xl font-semibold mb-2">Prochaines sessions programmées</h2>
          <div className="space-y-2">
            {prochainesSessions.map((session: any, idx: number) => (
              <div key={idx} className="p-3 rounded border flex items-center gap-4">
                <Calendar className="h-4 w-4 text-green-500" />
                <span className="font-medium">{session.titre || "Session"}</span>
                <span className="text-muted-foreground">{session.date ? new Date(session.date).toLocaleString("fr-FR") : "Date inconnue"}</span>
              </div>
            ))}
          </div>
        </div>
      )}
      {/* Affichage des étudiants mentorés amélioré */}
      {data.etudiants && (
        <div className="grid gap-4 lg:grid-cols-2 mt-8">
          <Card>
            <CardHeader className="py-4">
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle className="text-base">Étudiants mentorés</CardTitle>
                  <CardDescription>Liste de vos mentorés</CardDescription>
                </div>
                <Users className="h-4 w-4 text-muted-foreground" />
              </div>
            </CardHeader>
            <Separator />
            <CardContent className="p-0">
              <div className="divide-y">
                {data.etudiants.length > 0 ? (
                  data.etudiants.map((etudiant: any) => (
                    <div key={etudiant.id} className="flex items-center gap-3 px-4 py-3 hover:bg-muted/30 transition-colors">
                      <Avatar className="h-9 w-9">
                        <AvatarImage src={etudiant.avatar || undefined} />
                        <AvatarFallback className="bg-primary/10 text-primary text-sm">
                          {getInitials(etudiant.prenom, etudiant.nom)}
                        </AvatarFallback>
                      </Avatar>
                      <div className="flex-1 min-w-0">
                        <p className="text-sm font-medium truncate">{etudiant.prenom} {etudiant.nom}</p>
                        {etudiant.progression !== undefined && (
                          <div className="flex items-center gap-2 mt-2">
                            <Progress value={etudiant.progression} className="h-1.5 flex-1" />
                            <span className="text-xs text-muted-foreground">{etudiant.progression}%</span>
                          </div>
                        )}
                      </div>
                      <div className="flex flex-col items-end gap-1">
                        {etudiant.badges && etudiant.badges.length > 0 && (
                          <span className="text-xs text-amber-600 bg-amber-500/10 px-2 py-1 rounded font-semibold">{etudiant.badges.length} badge(s)</span>
                        )}
                        {etudiant.progression >= 80 && (
                          <span className="text-xs text-green-600 bg-green-500/10 px-2 py-1 rounded font-semibold">Excellent</span>
                        )}
                      </div>
                    </div>
                  ))
                ) : (
                  <div className="px-4 py-8 text-center text-muted-foreground text-sm">
                    Aucun étudiant mentoré pour l'instant.
                  </div>
                )}
              </div>
            </CardContent>
          </Card>
        </div>
      )}
    </div>
  );
}
