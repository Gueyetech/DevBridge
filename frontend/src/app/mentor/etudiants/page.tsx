"use client";
import { useEffect, useState } from "react";
import { apiClient } from "@/lib/api";
// import { Card, CardContent } from "@/components/ui/card";
import { Progress } from "@/components/ui/progress";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Loader2, Users, Calendar, FileText, Table as TableIcon } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { Card, CardContent } from "@/components/ui/card";
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from "@/components/ui/table";

type EtudiantMentore = {
  etudiant: {
    id: string;
    prenom: string;
    nom: string;
    profil?: { avatar?: string };
    competences?: any[];
  };
  progression_moyenne: number;
  sessions_total: number;
  derniere_session?: any;
  derniere_activite?: any;
  mentorat_depuis?: string;
};


export default function MentorEtudiantsPage() {
  const [etudiants, setEtudiants] = useState<EtudiantMentore[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [stats, setStats] = useState<{ total_etudiants: number; sessions_ce_mois: number; feedback_donnes: number; temps_total_mentorat: number }>({ total_etudiants: 0, sessions_ce_mois: 0, feedback_donnes: 0, temps_total_mentorat: 0 });

  useEffect(() => {
    setIsLoading(true);
    apiClient.get("/v1/mentor/mentorat/etudiants")
      .then((res: any) => {
        setEtudiants(res.data?.etudiants || []);
        if (res.data?.statistiques) {
          setStats(res.data.statistiques);
        }
        setIsLoading(false);
      })
      .catch(() => {
        setError("Erreur lors du chargement des étudiants mentorés.");
        setIsLoading(false);
      });
  }, []);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Gestion des étudiants mentorés</h1>
          <p className="text-muted-foreground">Liste de tous vos étudiants mentorés et progression</p>
        </div>
      </div>
      {/* Statistiques */}
      <div className="grid gap-3 grid-cols-2 md:grid-cols-4 xl:grid-cols-5">
        <Card className="border-l-4 border-l-blue-500 py-3">
          <CardContent className="flex items-center gap-3 p-0 px-4">
            <div className="p-2 rounded-full bg-blue-500/10"><Users className="h-4 w-4 text-blue-500" /></div>
            <div>
              <p className="text-2xl font-bold">{stats.total_etudiants}</p>
              <p className="text-xs text-muted-foreground">Total étudiants</p>
            </div>
          </CardContent>
        </Card>
        <Card className="border-l-4 border-l-green-500 py-3">
          <CardContent className="flex items-center gap-3 p-0 px-4">
            <div className="p-2 rounded-full bg-green-500/10"><Calendar className="h-4 w-4 text-green-500" /></div>
            <div>
              <p className="text-2xl font-bold">{stats.sessions_ce_mois}</p>
              <p className="text-xs text-muted-foreground">Sessions ce mois</p>
            </div>
          </CardContent>
        </Card>
        <Card className="border-l-4 border-l-purple-500 py-3">
          <CardContent className="flex items-center gap-3 p-0 px-4">
            <div className="p-2 rounded-full bg-purple-500/10"><FileText className="h-4 w-4 text-purple-500" /></div>
            <div>
              <p className="text-2xl font-bold">{stats.feedback_donnes}</p>
              <p className="text-xs text-muted-foreground">Feedback donnés</p>
            </div>
          </CardContent>
        </Card>
        <Card className="border-l-4 border-l-orange-500 py-3">
          <CardContent className="flex items-center gap-3 p-0 px-4">
            <div className="p-2 rounded-full bg-orange-500/10"><Users className="h-4 w-4 text-orange-500" /></div>
            <div>
              <p className="text-2xl font-bold">{Math.max(0, Math.round(stats.temps_total_mentorat/60))}h</p>
              <p className="text-xs text-muted-foreground">Heures mentorat</p>
            </div>
          </CardContent>
        </Card>
      </div>
      <Separator className="my-6" />
      {isLoading ? (
        <div className="flex items-center justify-center min-h-40"><Loader2 className="h-8 w-8 animate-spin" /></div>
      ) : error ? (
        <div className="text-red-600 py-8 text-center">{error}</div>
      ) : etudiants.length === 0 ? (
        <div className="text-muted-foreground py-8 text-center">Aucun étudiant mentoré pour le moment.</div>
      ) : (
        <div className="overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Étudiant</TableHead>
                <TableHead>Compétences validées</TableHead>
                <TableHead>Progression</TableHead>
                <TableHead>Sessions</TableHead>
                <TableHead>Dernière session</TableHead>
                <TableHead>Mentoré depuis</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {etudiants.map((item) => (
                <TableRow key={item.etudiant.id}>
                  <TableCell>
                    <div className="flex items-center gap-2">
                      <Avatar className="h-8 w-8">
                        <AvatarImage src={item.etudiant.profil?.avatar || undefined} />
                        <AvatarFallback>{item.etudiant.prenom.charAt(0)}{item.etudiant.nom.charAt(0)}</AvatarFallback>
                      </Avatar>
                      <span className="font-semibold">{item.etudiant.prenom} {item.etudiant.nom}</span>
                    </div>
                  </TableCell>
                  <TableCell>
                    {item.etudiant.competences && item.etudiant.competences.length > 0 ? (
                      <div className="flex flex-wrap gap-1">
                        {item.etudiant.competences.map((comp: any) => (
                          <Badge key={comp.id} variant="secondary" className="text-xs px-2 py-1">
                            {comp.nom} <span className="ml-1 text-[10px] text-muted-foreground">(Niv. {comp.pivot?.niveau_maitrise ?? '-'})</span>
                          </Badge>
                        ))}
                      </div>
                    ) : <span className="text-muted-foreground text-xs">-</span>}
                  </TableCell>
                  <TableCell>
                    <div className="flex items-center gap-2">
                      <Progress value={item.progression_moyenne} className="w-24 h-2" />
                      <span className="text-xs font-semibold">{Math.round(item.progression_moyenne)}%</span>
                    </div>
                  </TableCell>
                  <TableCell>{item.sessions_total}</TableCell>
                  <TableCell>{item.derniere_session ? new Date(item.derniere_session.date_debut).toLocaleDateString("fr-FR") : "-"}</TableCell>
                  <TableCell>{item.mentorat_depuis ? new Date(item.mentorat_depuis).toLocaleDateString("fr-FR") : "-"}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
      )}
    </div>
  );
}
