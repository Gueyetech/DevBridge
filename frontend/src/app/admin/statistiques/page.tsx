"use client";

import React, { useState, useEffect } from "react";
import { apiClient } from "@/lib/api";
import {
  Loader2,
  Users,
  TrendingUp,
  Clock,
  Activity,
  BarChart3,
  PieChart,
  ArrowUpRight,
  ArrowDownRight,
  Download,
  RefreshCw,
  Calendar,
} from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { Progress } from "@/components/ui/progress";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { toast } from "sonner";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";

interface StatistiquesGlobales {
  utilisation: {
    utilisateurs_actifs_jour: number;
    utilisateurs_actifs_semaine: number;
    utilisateurs_actifs_mois: number;
    sessions_aujourdhui: number;
    duree_moyenne_session: number;
  };
  engagement: {
    lecons_completees_jour: number;
    lecons_completees_semaine: number;
    quiz_passes_jour: number;
    projets_actifs: number;
    messages_forum_jour: number;
  };
  croissance: {
    nouveaux_utilisateurs_semaine: number;
    nouveaux_utilisateurs_mois: number;
    taux_croissance: number;
    tendance: string;
  };
  repartition: {
    par_role: Record<string, number>;
    par_niveau: Record<string, number>;
  };
}

interface EngagementData {
  engagement: {
    taux_completion_parcours: number;
    taux_participation_quiz: number;
    taux_participation_projets: number;
    taux_participation_forum: number;
    temps_moyen_plateforme: number;
    utilisateurs_actifs_par_jour: Array<{ date: string; count: number }>;
  };
}

interface PerformanceData {
  performance: {
    performance_parcours: Array<{ id: string; titre: string; taux_completion: number; note_moyenne: number }>;
    performance_projets: Array<{ id: string; nom: string; membres: number; statut: string }>;
    satisfaction: { note_moyenne: number; total_avis: number };
  };
}

interface RepartitionData {
  repartition: {
    technologies_populaires: Array<{ technologie: string; count: number }>;
    parcours_par_difficulte: Record<string, number>;
    utilisateurs_par_niveau: Record<string, number>;
  };
}

export default function StatistiquesPage() {
  const [isLoading, setIsLoading] = useState(true);
  const [periode, setPeriode] = useState("30");
  const [stats, setStats] = useState<StatistiquesGlobales | null>(null);
  const [engagement, setEngagement] = useState<EngagementData | null>(null);
  const [performance, setPerformance] = useState<PerformanceData | null>(null);
  const [repartition, setRepartition] = useState<RepartitionData | null>(null);
  const [activeTab, setActiveTab] = useState("apercu");

  const fetchStats = async () => {
    setIsLoading(true);
    try {
      const [statsRes, engagementRes, performanceRes, repartitionRes] = await Promise.all([
        apiClient.get<{ success: boolean; data: StatistiquesGlobales }>(`/v1/admin/analytiques/utilisation?periode=${periode}`),
        apiClient.get<{ success: boolean; data: EngagementData }>(`/v1/admin/analytiques/engagement?periode=${periode}`),
        apiClient.get<{ success: boolean; data: PerformanceData }>("/v1/admin/analytiques/performance"),
        apiClient.get<{ success: boolean; data: RepartitionData }>("/v1/admin/analytiques/repartition"),
      ]);
      setStats(statsRes.data);
      setEngagement(engagementRes.data);
      setPerformance(performanceRes.data);
      setRepartition(repartitionRes.data);
    } catch (error) {
      console.error("Erreur:", error);
      toast.error("Erreur lors du chargement des statistiques");
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchStats();
  }, [periode]);

  const handleExport = async (type: string) => {
    try {
      const response = await apiClient.get<{ success: boolean; data: { donnees: unknown[] } }>(`/v1/admin/analytiques/export/${type}`);
      const blob = new Blob([JSON.stringify(response.data.donnees, null, 2)], { type: "application/json" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `export-${type}-${new Date().toISOString().split("T")[0]}.json`;
      a.click();
      toast.success(`Export ${type} téléchargé`);
    } catch (error) {
      console.error("Erreur:", error);
      toast.error("Erreur lors de l'export");
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <Loader2 className="h-8 w-8 animate-spin" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Statistiques</h1>
          <p className="text-muted-foreground">Analysez les performances de la plateforme</p>
        </div>
        <div className="flex items-center gap-2">
          <Select value={periode} onValueChange={setPeriode}>
            <SelectTrigger className="w-[150px]">
              <Calendar className="h-4 w-4 mr-2" />
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="7">7 derniers jours</SelectItem>
              <SelectItem value="30">30 derniers jours</SelectItem>
              <SelectItem value="90">90 derniers jours</SelectItem>
            </SelectContent>
          </Select>
          <Button variant="outline" size="icon" onClick={fetchStats}>
            <RefreshCw className="h-4 w-4" />
          </Button>
        </div>
      </div>

      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList className="grid w-full grid-cols-4 lg:w-[500px]">
          <TabsTrigger value="apercu">Aperçu</TabsTrigger>
          <TabsTrigger value="engagement">Engagement</TabsTrigger>
          <TabsTrigger value="performance">Performance</TabsTrigger>
          <TabsTrigger value="repartition">Répartition</TabsTrigger>
        </TabsList>

        {/* Onglet Aperçu */}
        <TabsContent value="apercu" className="space-y-6 mt-6">
          {/* Stats principales */}
          <div className="grid gap-3 grid-cols-2 lg:grid-cols-4">
            <Card className="border-l-4 border-l-blue-500 py-3">
              <CardContent className="flex items-center gap-3 p-0 px-4">
                <div className="p-2 rounded-full bg-blue-500/10">
                  <Users className="h-4 w-4 text-blue-500" />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-2xl font-bold">{stats?.utilisation?.utilisateurs_actifs_jour || 0}</p>
                  <p className="text-xs text-muted-foreground">Actifs aujourd&apos;hui</p>
                </div>
              </CardContent>
            </Card>
            <Card className="border-l-4 border-l-green-500 py-3">
              <CardContent className="flex items-center gap-3 p-0 px-4">
                <div className="p-2 rounded-full bg-green-500/10">
                  <TrendingUp className="h-4 w-4 text-green-500" />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-2xl font-bold">{stats?.croissance?.nouveaux_utilisateurs_semaine || 0}</p>
                  <p className="text-xs text-muted-foreground">Nouveaux cette semaine</p>
                </div>
                {stats?.croissance?.taux_croissance !== undefined && (
                  <Badge variant="outline" className={`text-xs ${stats.croissance.taux_croissance >= 0 ? "text-green-600 border-green-500/50 bg-green-500/10" : "text-red-600 border-red-500/50 bg-red-500/10"}`}>
                    {stats.croissance.taux_croissance >= 0 ? <ArrowUpRight className="h-3 w-3 mr-0.5" /> : <ArrowDownRight className="h-3 w-3 mr-0.5" />}
                    {Math.abs(stats.croissance.taux_croissance)}%
                  </Badge>
                )}
              </CardContent>
            </Card>
            <Card className="border-l-4 border-l-purple-500 py-3">
              <CardContent className="flex items-center gap-3 p-0 px-4">
                <div className="p-2 rounded-full bg-purple-500/10">
                  <Activity className="h-4 w-4 text-purple-500" />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-2xl font-bold">{stats?.utilisation?.sessions_aujourdhui || 0}</p>
                  <p className="text-xs text-muted-foreground">Sessions aujourd&apos;hui</p>
                </div>
              </CardContent>
            </Card>
            <Card className="border-l-4 border-l-amber-500 py-3">
              <CardContent className="flex items-center gap-3 p-0 px-4">
                <div className="p-2 rounded-full bg-amber-500/10">
                  <Clock className="h-4 w-4 text-amber-500" />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-2xl font-bold">{stats?.utilisation?.duree_moyenne_session || 0}min</p>
                  <p className="text-xs text-muted-foreground">Durée moyenne session</p>
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Graphiques en 2 colonnes */}
          <div className="grid gap-4 lg:grid-cols-2">
            {/* Répartition par rôle */}
            <Card>
              <CardHeader className="py-4">
                <div className="flex items-center justify-between">
                  <div>
                    <CardTitle className="text-base">Répartition par rôle</CardTitle>
                    <CardDescription>Distribution des utilisateurs</CardDescription>
                  </div>
                  <PieChart className="h-4 w-4 text-muted-foreground" />
                </div>
              </CardHeader>
              <Separator />
              <CardContent className="py-4">
                <div className="space-y-4">
                  {Object.entries(stats?.repartition?.par_role || {}).map(([role, count]) => {
                    const total = Object.values(stats?.repartition?.par_role || {}).reduce((a, b) => a + b, 0);
                    const percentage = total > 0 ? Math.round((count / total) * 100) : 0;
                    return (
                      <div key={role} className="space-y-2">
                        <div className="flex items-center justify-between text-sm">
                          <span className="capitalize">{role}</span>
                          <span className="font-medium">{count} ({percentage}%)</span>
                        </div>
                        <Progress value={percentage} className="h-2" />
                      </div>
                    );
                  })}
                </div>
              </CardContent>
            </Card>

            {/* Engagement quotidien */}
            <Card>
              <CardHeader className="py-4">
                <div className="flex items-center justify-between">
                  <div>
                    <CardTitle className="text-base">Engagement quotidien</CardTitle>
                    <CardDescription>Activités des utilisateurs</CardDescription>
                  </div>
                  <BarChart3 className="h-4 w-4 text-muted-foreground" />
                </div>
              </CardHeader>
              <Separator />
              <CardContent className="py-4">
                <div className="space-y-3">
                  <div className="flex items-center justify-between p-2 rounded bg-muted/30">
                    <span className="text-sm">Leçons complétées</span>
                    <Badge variant="outline">{stats?.engagement?.lecons_completees_jour || 0} aujourd&apos;hui</Badge>
                  </div>
                  <div className="flex items-center justify-between p-2 rounded bg-muted/30">
                    <span className="text-sm">Quiz passés</span>
                    <Badge variant="outline">{stats?.engagement?.quiz_passes_jour || 0} aujourd&apos;hui</Badge>
                  </div>
                  <div className="flex items-center justify-between p-2 rounded bg-muted/30">
                    <span className="text-sm">Projets actifs</span>
                    <Badge variant="outline">{stats?.engagement?.projets_actifs || 0}</Badge>
                  </div>
                  <div className="flex items-center justify-between p-2 rounded bg-muted/30">
                    <span className="text-sm">Messages forum</span>
                    <Badge variant="outline">{stats?.engagement?.messages_forum_jour || 0} aujourd&apos;hui</Badge>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* Onglet Engagement */}
        <TabsContent value="engagement" className="space-y-6 mt-6">
          <div className="grid gap-3 grid-cols-2 lg:grid-cols-4">
            <Card className="py-3">
              <CardContent className="flex items-center justify-between p-0 px-4">
                <div>
                  <p className="text-2xl font-bold">{engagement?.engagement?.taux_completion_parcours || 0}%</p>
                  <p className="text-xs text-muted-foreground">Taux complétion parcours</p>
                </div>
              </CardContent>
            </Card>
            <Card className="py-3">
              <CardContent className="flex items-center justify-between p-0 px-4">
                <div>
                  <p className="text-2xl font-bold">{engagement?.engagement?.taux_participation_quiz || 0}%</p>
                  <p className="text-xs text-muted-foreground">Participation quiz</p>
                </div>
              </CardContent>
            </Card>
            <Card className="py-3">
              <CardContent className="flex items-center justify-between p-0 px-4">
                <div>
                  <p className="text-2xl font-bold">{engagement?.engagement?.taux_participation_projets || 0}%</p>
                  <p className="text-xs text-muted-foreground">Participation projets</p>
                </div>
              </CardContent>
            </Card>
            <Card className="py-3">
              <CardContent className="flex items-center justify-between p-0 px-4">
                <div>
                  <p className="text-2xl font-bold">{engagement?.engagement?.temps_moyen_plateforme || 0}min</p>
                  <p className="text-xs text-muted-foreground">Temps moyen/jour</p>
                </div>
              </CardContent>
            </Card>
          </div>

          <Card>
            <CardHeader className="py-4">
              <CardTitle className="text-base">Activité des utilisateurs par jour</CardTitle>
            </CardHeader>
            <Separator />
            <CardContent className="py-4">
              {engagement?.engagement?.utilisateurs_actifs_par_jour && engagement.engagement.utilisateurs_actifs_par_jour.length > 0 ? (
                <div className="flex items-end gap-1 h-40">
                  {engagement.engagement.utilisateurs_actifs_par_jour.slice(-14).map((day, index) => {
                    const max = Math.max(...engagement.engagement.utilisateurs_actifs_par_jour.map(d => d.count));
                    const height = max > 0 ? (day.count / max) * 100 : 0;
                    return (
                      <div key={index} className="flex-1 flex flex-col items-center gap-1">
                        <div className="w-full bg-primary/20 rounded-t relative" style={{ height: `${height}%`, minHeight: "4px" }}>
                          <div className="absolute -top-5 left-1/2 -translate-x-1/2 text-xs opacity-0 hover:opacity-100">{day.count}</div>
                        </div>
                        <span className="text-xs text-muted-foreground rotate-45 origin-left">{new Date(day.date).toLocaleDateString("fr-FR", { day: "numeric", month: "short" })}</span>
                      </div>
                    );
                  })}
                </div>
              ) : (
                <p className="text-center text-muted-foreground py-8">Aucune donnée disponible</p>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* Onglet Performance */}
        <TabsContent value="performance" className="space-y-6 mt-6">
          <div className="grid gap-4 lg:grid-cols-2">
            <Card>
              <CardHeader className="py-4">
                <CardTitle className="text-base">Performance des parcours</CardTitle>
              </CardHeader>
              <Separator />
              <CardContent className="p-0">
                <Table>
                  <TableHeader>
                    <TableRow className="bg-muted/50">
                      <TableHead>Parcours</TableHead>
                      <TableHead>Complétion</TableHead>
                      <TableHead>Note moy.</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {performance?.performance?.performance_parcours?.slice(0, 5).map((p) => (
                      <TableRow key={p.id}>
                        <TableCell className="font-medium">{p.titre}</TableCell>
                        <TableCell>
                          <div className="flex items-center gap-2">
                            <Progress value={p.taux_completion} className="h-2 w-16" />
                            <span className="text-sm">{p.taux_completion}%</span>
                          </div>
                        </TableCell>
                        <TableCell>
                          <Badge variant="outline">{p.note_moyenne}/20</Badge>
                        </TableCell>
                      </TableRow>
                    ))}
                    {(!performance?.performance?.performance_parcours || performance.performance.performance_parcours.length === 0) && (
                      <TableRow>
                        <TableCell colSpan={3} className="text-center text-muted-foreground py-8">
                          Aucune donnée
                        </TableCell>
                      </TableRow>
                    )}
                  </TableBody>
                </Table>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="py-4">
                <CardTitle className="text-base">Projets actifs</CardTitle>
              </CardHeader>
              <Separator />
              <CardContent className="p-0">
                <Table>
                  <TableHeader>
                    <TableRow className="bg-muted/50">
                      <TableHead>Projet</TableHead>
                      <TableHead>Membres</TableHead>
                      <TableHead>Statut</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {performance?.performance?.performance_projets?.slice(0, 5).map((p) => (
                      <TableRow key={p.id}>
                        <TableCell className="font-medium">{p.nom}</TableCell>
                        <TableCell>{p.membres}</TableCell>
                        <TableCell>
                          <Badge variant="outline" className={p.statut === "en_cours" ? "bg-blue-500/10 text-blue-600" : "bg-green-500/10 text-green-600"}>
                            {p.statut === "en_cours" ? "En cours" : p.statut}
                          </Badge>
                        </TableCell>
                      </TableRow>
                    ))}
                    {(!performance?.performance?.performance_projets || performance.performance.performance_projets.length === 0) && (
                      <TableRow>
                        <TableCell colSpan={3} className="text-center text-muted-foreground py-8">
                          Aucune donnée
                        </TableCell>
                      </TableRow>
                    )}
                  </TableBody>
                </Table>
              </CardContent>
            </Card>
          </div>

          {performance?.performance?.satisfaction && (
            <Card>
              <CardContent className="py-6">
                <div className="flex items-center justify-center gap-8">
                  <div className="text-center">
                    <p className="text-4xl font-bold text-primary">{performance.performance.satisfaction.note_moyenne}/5</p>
                    <p className="text-sm text-muted-foreground">Note de satisfaction</p>
                  </div>
                  <Separator orientation="vertical" className="h-16" />
                  <div className="text-center">
                    <p className="text-4xl font-bold">{performance.performance.satisfaction.total_avis}</p>
                    <p className="text-sm text-muted-foreground">Avis collectés</p>
                  </div>
                </div>
              </CardContent>
            </Card>
          )}
        </TabsContent>

        {/* Onglet Répartition */}
        <TabsContent value="repartition" className="space-y-6 mt-6">
          <div className="grid gap-4 lg:grid-cols-2">
            {/* Technologies populaires */}
            <Card>
              <CardHeader className="py-4">
                <CardTitle className="text-base">Technologies populaires</CardTitle>
              </CardHeader>
              <Separator />
              <CardContent className="py-4">
                <div className="space-y-3">
                  {repartition?.repartition?.technologies_populaires?.slice(0, 8).map((tech, index) => {
                    const max = Math.max(...(repartition.repartition.technologies_populaires?.map(t => t.count) || [1]));
                    const percentage = max > 0 ? Math.round((tech.count / max) * 100) : 0;
                    return (
                      <div key={tech.technologie} className="flex items-center gap-3">
                        <span className="w-6 h-6 rounded-full bg-primary/10 flex items-center justify-center text-xs font-medium text-primary">
                          {index + 1}
                        </span>
                        <div className="flex-1">
                          <div className="flex items-center justify-between text-sm mb-1">
                            <span>{tech.technologie}</span>
                            <span className="text-muted-foreground">{tech.count}</span>
                          </div>
                          <Progress value={percentage} className="h-1.5" />
                        </div>
                      </div>
                    );
                  })}
                  {(!repartition?.repartition?.technologies_populaires || repartition.repartition.technologies_populaires.length === 0) && (
                    <p className="text-center text-muted-foreground py-4">Aucune donnée</p>
                  )}
                </div>
              </CardContent>
            </Card>

            {/* Parcours par difficulté */}
            <Card>
              <CardHeader className="py-4">
                <CardTitle className="text-base">Parcours par difficulté</CardTitle>
              </CardHeader>
              <Separator />
              <CardContent className="py-4">
                <div className="space-y-4">
                  {Object.entries(repartition?.repartition?.parcours_par_difficulte || {}).map(([niveau, count]) => {
                    const colors: Record<string, string> = {
                      debutant: "bg-green-500",
                      intermediaire: "bg-yellow-500",
                      avance: "bg-orange-500",
                      expert: "bg-red-500",
                    };
                    const labels: Record<string, string> = {
                      debutant: "Débutant",
                      intermediaire: "Intermédiaire",
                      avance: "Avancé",
                      expert: "Expert",
                    };
                    return (
                      <div key={niveau} className="flex items-center gap-3">
                        <div className={`w-3 h-3 rounded-full ${colors[niveau] || "bg-gray-500"}`} />
                        <span className="flex-1 text-sm">{labels[niveau] || niveau}</span>
                        <Badge variant="outline">{count}</Badge>
                      </div>
                    );
                  })}
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Export */}
          <Card>
            <CardHeader className="py-4">
              <CardTitle className="text-base">Exporter les données</CardTitle>
              <CardDescription>Téléchargez les données en format JSON</CardDescription>
            </CardHeader>
            <Separator />
            <CardContent className="py-4">
              <div className="flex flex-wrap gap-2">
                <Button variant="outline" size="sm" onClick={() => handleExport("utilisateurs")}>
                  <Download className="h-4 w-4 mr-2" /> Utilisateurs
                </Button>
                <Button variant="outline" size="sm" onClick={() => handleExport("parcours")}>
                  <Download className="h-4 w-4 mr-2" /> Parcours
                </Button>
                <Button variant="outline" size="sm" onClick={() => handleExport("projets")}>
                  <Download className="h-4 w-4 mr-2" /> Projets
                </Button>
                <Button variant="outline" size="sm" onClick={() => handleExport("mentorat")}>
                  <Download className="h-4 w-4 mr-2" /> Mentorat
                </Button>
                <Button variant="outline" size="sm" onClick={() => handleExport("logs")}>
                  <Download className="h-4 w-4 mr-2" /> Logs
                </Button>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
