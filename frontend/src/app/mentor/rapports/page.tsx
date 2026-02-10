"use client";
import { useEffect, useState, useCallback } from "react";
import { apiClient } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Loader2, Users, Calendar, BrainCircuit, Activity, TrendingUp } from "lucide-react";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";

export default function MentorRapportsPage() {
  const [rapportEtudiants, setRapportEtudiants] = useState<any>(null);
  const [rapportSessions, setRapportSessions] = useState<any>(null);
  const [rapportCompetences, setRapportCompetences] = useState<any>(null);
  const [rapportActivite, setRapportActivite] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [tab, setTab] = useState("etudiants");

  const fetchAll = useCallback(async () => {
    setLoading(true);
    try {
      const [e, s, c, a] = await Promise.all([
        apiClient.get<any>("/v1/mentor/rapports/etudiants").catch(() => ({ data: {} })),
        apiClient.get<any>("/v1/mentor/rapports/sessions").catch(() => ({ data: {} })),
        apiClient.get<any>("/v1/mentor/rapports/competences").catch(() => ({ data: {} })),
        apiClient.get<any>("/v1/mentor/rapports/activite").catch(() => ({ data: {} })),
      ]);
      setRapportEtudiants(e.data);
      setRapportSessions(s.data);
      setRapportCompetences(c.data);
      setRapportActivite(a.data);
    } catch (e: any) { setError(e.message); }
    setLoading(false);
  }, []);

  useEffect(() => { fetchAll(); }, [fetchAll]);

  if (loading) return <div className="py-12 text-center"><Loader2 className="h-8 w-8 animate-spin mx-auto" /></div>;

  return (
    <div className="space-y-6">
      <div><h1 className="text-2xl font-bold flex items-center gap-2"><TrendingUp className="h-6 w-6" /> Rapports</h1><p className="text-muted-foreground">Analysez votre activité de mentorat</p></div>
      {error && <div className="text-red-600 text-sm bg-red-50 p-3 rounded-lg">{error}</div>}

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList>
          <TabsTrigger value="etudiants"><Users className="h-3 w-3 mr-1" /> Étudiants</TabsTrigger>
          <TabsTrigger value="sessions"><Calendar className="h-3 w-3 mr-1" /> Sessions</TabsTrigger>
          <TabsTrigger value="competences"><BrainCircuit className="h-3 w-3 mr-1" /> Compétences</TabsTrigger>
          <TabsTrigger value="activite"><Activity className="h-3 w-3 mr-1" /> Activité</TabsTrigger>
        </TabsList>

        <TabsContent value="etudiants" className="mt-4 space-y-4">
          <Card><CardHeader><CardTitle>Rapport Étudiants</CardTitle></CardHeader>
            <CardContent>
              {rapportEtudiants?.statistiques && (
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                  {Object.entries(rapportEtudiants.statistiques).map(([k, v]) => (
                    <div key={k} className="text-center"><p className="text-2xl font-bold">{String(v)}</p><p className="text-xs text-muted-foreground capitalize">{k.replace(/_/g, " ")}</p></div>
                  ))}
                </div>
              )}
              {rapportEtudiants?.etudiants && Array.isArray(rapportEtudiants.etudiants) && (
                <Table>
                  <TableHeader><TableRow><TableHead>Nom</TableHead><TableHead>Progression</TableHead><TableHead>Sessions</TableHead><TableHead>Statut</TableHead></TableRow></TableHeader>
                  <TableBody>
                    {rapportEtudiants.etudiants.map((e: any, i: number) => (
                      <TableRow key={i}><TableCell>{e.prenom || e.etudiant?.prenom} {e.nom || e.etudiant?.nom}</TableCell><TableCell>{e.progression_moyenne || e.progression || 0}%</TableCell><TableCell>{e.sessions_total || e.sessions || 0}</TableCell><TableCell><Badge variant="outline">{e.statut || "actif"}</Badge></TableCell></TableRow>
                    ))}
                  </TableBody>
                </Table>
              )}
              {!rapportEtudiants?.etudiants && !rapportEtudiants?.statistiques && <p className="text-muted-foreground text-center py-8">Aucune donnée disponible.</p>}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="sessions" className="mt-4 space-y-4">
          <Card><CardHeader><CardTitle>Rapport Sessions</CardTitle></CardHeader>
            <CardContent>
              {rapportSessions?.statistiques && (
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                  {Object.entries(rapportSessions.statistiques).map(([k, v]) => (
                    <div key={k} className="text-center"><p className="text-2xl font-bold">{String(v)}</p><p className="text-xs text-muted-foreground capitalize">{k.replace(/_/g, " ")}</p></div>
                  ))}
                </div>
              )}
              {rapportSessions?.sessions && Array.isArray(rapportSessions.sessions) && (
                <Table>
                  <TableHeader><TableRow><TableHead>Date</TableHead><TableHead>Durée</TableHead><TableHead>Type</TableHead><TableHead>Statut</TableHead></TableRow></TableHeader>
                  <TableBody>
                    {rapportSessions.sessions.map((s: any, i: number) => (
                      <TableRow key={i}><TableCell>{s.date_session ? new Date(s.date_session).toLocaleDateString("fr-FR") : "-"}</TableCell><TableCell>{s.duree || 0} min</TableCell><TableCell>{s.type_session || "-"}</TableCell><TableCell><Badge variant="outline">{s.statut}</Badge></TableCell></TableRow>
                    ))}
                  </TableBody>
                </Table>
              )}
              {!rapportSessions?.sessions && !rapportSessions?.statistiques && <p className="text-muted-foreground text-center py-8">Aucune donnée disponible.</p>}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="competences" className="mt-4 space-y-4">
          <Card><CardHeader><CardTitle>Rapport Compétences</CardTitle></CardHeader>
            <CardContent>
              {rapportCompetences?.statistiques && (
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                  {Object.entries(rapportCompetences.statistiques).map(([k, v]) => (
                    <div key={k} className="text-center"><p className="text-2xl font-bold">{String(v)}</p><p className="text-xs text-muted-foreground capitalize">{k.replace(/_/g, " ")}</p></div>
                  ))}
                </div>
              )}
              {rapportCompetences?.competences && Array.isArray(rapportCompetences.competences) && (
                <div className="flex flex-wrap gap-2">
                  {rapportCompetences.competences.map((c: any, i: number) => (
                    <Badge key={i} variant="secondary" className="px-3 py-1">{c.nom || c.competence} {c.count ? `(${c.count})` : ""}</Badge>
                  ))}
                </div>
              )}
              {!rapportCompetences?.competences && !rapportCompetences?.statistiques && <p className="text-muted-foreground text-center py-8">Aucune donnée disponible.</p>}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="activite" className="mt-4 space-y-4">
          <Card><CardHeader><CardTitle>Rapport Activité</CardTitle></CardHeader>
            <CardContent>
              {rapportActivite?.statistiques && (
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                  {Object.entries(rapportActivite.statistiques).map(([k, v]) => (
                    <div key={k} className="text-center"><p className="text-2xl font-bold">{String(v)}</p><p className="text-xs text-muted-foreground capitalize">{k.replace(/_/g, " ")}</p></div>
                  ))}
                </div>
              )}
              {rapportActivite?.activites && Array.isArray(rapportActivite.activites) && (
                <div className="space-y-2">
                  {rapportActivite.activites.map((a: any, i: number) => (
                    <div key={i} className="flex items-center gap-3 p-2 bg-muted rounded-lg">
                      <Activity className="h-4 w-4 text-muted-foreground" />
                      <div className="flex-1"><p className="text-sm">{a.description || a.type}</p><p className="text-xs text-muted-foreground">{a.created_at ? new Date(a.created_at).toLocaleString("fr-FR") : ""}</p></div>
                    </div>
                  ))}
                </div>
              )}
              {!rapportActivite?.activites && !rapportActivite?.statistiques && <p className="text-muted-foreground text-center py-8">Aucune donnée disponible.</p>}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
