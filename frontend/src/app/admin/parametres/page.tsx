"use client";

import React, { useState, useEffect } from "react";
import { apiClient } from "@/lib/api";
import {
  Loader2,
  Settings,
  Database,
  FileText,
  Shield,
  Save,
  RefreshCw,
  Clock,
  AlertCircle,
  CheckCircle,
  Info,
  HardDrive,
  Download,
} from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { ScrollArea } from "@/components/ui/scroll-area";
import { toast } from "sonner";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "@/components/ui/alert-dialog";

interface Configuration {
  id: string;
  cle: string;
  valeur: string;
  type: string;
  description: string;
  categorie: string;
  est_modifiable: boolean;
}

interface Log {
  id: string;
  action: string;
  niveau: string;
  message: string;
  utilisateur_id: string | null;
  prenom: string | null;
  nom: string | null;
  email: string | null;
  ip_address: string;
  created_at: string;
}

interface Sauvegarde {
  id: string;
  nom: string;
  taille: number;
  type: string;
  created_at: string;
  statut: string;
}

export default function ParametresPage() {
  const [isLoading, setIsLoading] = useState(true);
  const [activeTab, setActiveTab] = useState("configurations");
  
  // Configurations
  const [configurations, setConfigurations] = useState<Record<string, Configuration[]>>({});
  const [modifiedConfigs, setModifiedConfigs] = useState<Record<string, string>>({});
  const [isSaving, setIsSaving] = useState(false);

  // Logs
  const [logs, setLogs] = useState<Log[]>([]);
  const [logsStats, setLogsStats] = useState<{ par_niveau: Record<string, number> }>({ par_niveau: {} });
  const [logFilters, setLogFilters] = useState({ niveau: "all", page: 1 });
  const [logsMeta, setLogsMeta] = useState({ total: 0, page_courante: 1 });

  // Sauvegardes
  const [sauvegardes, setSauvegardes] = useState<Sauvegarde[]>([]);
  const [isCreatingSauvegarde, setIsCreatingSauvegarde] = useState(false);

  const fetchConfigurations = async () => {
    try {
      const response = await apiClient.get<{ success: boolean; data: { configurations: Record<string, Configuration[]> } }>("/v1/admin/systeme/configurations");
      setConfigurations(response.data.configurations);
    } catch (error) {
      console.error("Erreur:", error);
      toast.error("Erreur lors du chargement des configurations");
    }
  };

  const fetchLogs = async () => {
    try {
      const params = new URLSearchParams({
        page: logFilters.page.toString(),
        per_page: "20",
      });
      if (logFilters.niveau !== "all") params.append("niveau", logFilters.niveau);
      
      const response = await apiClient.get<{ success: boolean; data: { logs: { data: Log[] }; statistiques: { par_niveau: Record<string, number> }; meta: { total: number; page_courante: number } } }>(`/v1/admin/systeme/logs?${params}`);
      setLogs(response.data.logs.data || []);
      setLogsStats(response.data.statistiques);
      setLogsMeta(response.data.meta);
    } catch (error) {
      console.error("Erreur:", error);
      toast.error("Erreur lors du chargement des logs");
    }
  };

  const fetchSauvegardes = async () => {
    try {
      const response = await apiClient.get<{ success: boolean; data: { sauvegardes: Sauvegarde[] } }>("/v1/admin/systeme/sauvegardes");
      setSauvegardes(response.data.sauvegardes || []);
    } catch (error) {
      console.error("Erreur:", error);
      toast.error("Erreur lors du chargement des sauvegardes");
    }
  };

  useEffect(() => {
    const loadData = async () => {
      setIsLoading(true);
      await Promise.all([fetchConfigurations(), fetchLogs(), fetchSauvegardes()]);
      setIsLoading(false);
    };
    loadData();
  }, []);

  useEffect(() => {
    if (!isLoading) fetchLogs();
  }, [logFilters]);

  const handleConfigChange = (cle: string, valeur: string) => {
    setModifiedConfigs(prev => ({ ...prev, [cle]: valeur }));
  };

  const handleSaveConfig = async (cle: string) => {
    if (!modifiedConfigs[cle]) return;
    setIsSaving(true);
    try {
      await apiClient.put(`/v1/admin/systeme/configurations/${cle}`, { valeur: modifiedConfigs[cle] });
      toast.success("Configuration mise à jour");
      setModifiedConfigs(prev => {
        const newState = { ...prev };
        delete newState[cle];
        return newState;
      });
      fetchConfigurations();
    } catch (error) {
      console.error("Erreur:", error);
      toast.error("Erreur lors de la mise à jour");
    } finally {
      setIsSaving(false);
    }
  };

  const handleCreerSauvegarde = async () => {
    setIsCreatingSauvegarde(true);
    try {
      await apiClient.post("/v1/admin/systeme/sauvegardes");
      toast.success("Sauvegarde créée avec succès");
      fetchSauvegardes();
    } catch (error) {
      console.error("Erreur:", error);
      toast.error("Erreur lors de la création de la sauvegarde");
    } finally {
      setIsCreatingSauvegarde(false);
    }
  };

  const formatBytes = (bytes: number) => {
    if (bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
  };

  const formatDate = (date: string) => {
    return new Date(date).toLocaleString("fr-FR", {
      day: "numeric",
      month: "short",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  };

  const getLogLevelIcon = (niveau: string) => {
    switch (niveau) {
      case "error":
        return <AlertCircle className="h-4 w-4 text-red-500" />;
      case "warning":
        return <AlertCircle className="h-4 w-4 text-amber-500" />;
      case "info":
        return <Info className="h-4 w-4 text-blue-500" />;
      default:
        return <CheckCircle className="h-4 w-4 text-green-500" />;
    }
  };

  const getLogLevelBadge = (niveau: string) => {
    const styles: Record<string, string> = {
      error: "bg-red-500/10 text-red-600 border-red-500/30",
      warning: "bg-amber-500/10 text-amber-600 border-amber-500/30",
      info: "bg-blue-500/10 text-blue-600 border-blue-500/30",
      success: "bg-green-500/10 text-green-600 border-green-500/30",
    };
    return styles[niveau] || "bg-gray-500/10 text-gray-600 border-gray-500/30";
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
      <div>
        <h1 className="text-3xl font-bold">Paramètres système</h1>
        <p className="text-muted-foreground">Configurez et administrez la plateforme</p>
      </div>

      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList className="grid w-full grid-cols-3 lg:w-[400px]">
          <TabsTrigger value="configurations" className="gap-2">
            <Settings className="h-4 w-4" />
            Config
          </TabsTrigger>
          <TabsTrigger value="logs" className="gap-2">
            <FileText className="h-4 w-4" />
            Logs
          </TabsTrigger>
          <TabsTrigger value="sauvegardes" className="gap-2">
            <Database className="h-4 w-4" />
            Sauvegardes
          </TabsTrigger>
        </TabsList>

        {/* Onglet Configurations */}
        <TabsContent value="configurations" className="space-y-6 mt-6">
          {Object.entries(configurations).map(([categorie, configs]) => (
            <Card key={categorie}>
              <CardHeader className="py-4">
                <CardTitle className="text-base capitalize flex items-center gap-2">
                  <Shield className="h-4 w-4" />
                  {categorie}
                </CardTitle>
                <CardDescription>Paramètres de {categorie}</CardDescription>
              </CardHeader>
              <Separator />
              <CardContent className="py-4">
                <div className="space-y-4">
                  {configs.map((config) => (
                    <div key={config.cle} className="flex items-start gap-4 p-3 rounded-lg hover:bg-muted/30 transition-colors">
                      <div className="flex-1 space-y-1">
                        <Label htmlFor={config.cle} className="font-medium">{config.cle}</Label>
                        <p className="text-xs text-muted-foreground">{config.description}</p>
                      </div>
                      <div className="flex items-center gap-2">
                        {config.type === "boolean" ? (
                          <Switch
                            id={config.cle}
                            checked={modifiedConfigs[config.cle] !== undefined ? modifiedConfigs[config.cle] === "true" : config.valeur === "true"}
                            onCheckedChange={(checked) => handleConfigChange(config.cle, checked ? "true" : "false")}
                            disabled={!config.est_modifiable}
                          />
                        ) : config.type === "number" ? (
                          <Input
                            id={config.cle}
                            type="number"
                            value={modifiedConfigs[config.cle] ?? config.valeur}
                            onChange={(e) => handleConfigChange(config.cle, e.target.value)}
                            disabled={!config.est_modifiable}
                            className="w-24"
                          />
                        ) : (
                          <Input
                            id={config.cle}
                            value={modifiedConfigs[config.cle] ?? config.valeur}
                            onChange={(e) => handleConfigChange(config.cle, e.target.value)}
                            disabled={!config.est_modifiable}
                            className="w-48"
                          />
                        )}
                        {modifiedConfigs[config.cle] !== undefined && (
                          <Button size="sm" onClick={() => handleSaveConfig(config.cle)} disabled={isSaving}>
                            {isSaving ? <Loader2 className="h-4 w-4 animate-spin" /> : <Save className="h-4 w-4" />}
                          </Button>
                        )}
                        {!config.est_modifiable && (
                          <Badge variant="outline" className="text-xs">Lecture seule</Badge>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          ))}
          {Object.keys(configurations).length === 0 && (
            <Card>
              <CardContent className="py-12 text-center text-muted-foreground">
                Aucune configuration disponible
              </CardContent>
            </Card>
          )}
        </TabsContent>

        {/* Onglet Logs */}
        <TabsContent value="logs" className="space-y-6 mt-6">
          {/* Stats logs */}
          <div className="grid gap-3 grid-cols-2 lg:grid-cols-4">
            {Object.entries(logsStats.par_niveau || {}).map(([niveau, count]) => (
              <Card key={niveau} className="py-2">
                <CardContent className="flex items-center gap-3 p-0 px-4">
                  {getLogLevelIcon(niveau)}
                  <div>
                    <p className="text-lg font-bold">{count}</p>
                    <p className="text-xs text-muted-foreground capitalize">{niveau}</p>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>

          {/* Filtres */}
          <div className="flex items-center gap-3">
            <Select value={logFilters.niveau} onValueChange={(v) => setLogFilters(prev => ({ ...prev, niveau: v, page: 1 }))}>
              <SelectTrigger className="w-[150px]">
                <SelectValue placeholder="Niveau" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Tous les niveaux</SelectItem>
                <SelectItem value="error">Erreurs</SelectItem>
                <SelectItem value="warning">Avertissements</SelectItem>
                <SelectItem value="info">Info</SelectItem>
                <SelectItem value="success">Succès</SelectItem>
              </SelectContent>
            </Select>
            <Button variant="outline" size="icon" onClick={fetchLogs}>
              <RefreshCw className="h-4 w-4" />
            </Button>
          </div>

          {/* Table logs */}
          <Card>
            <CardContent className="p-0">
              <ScrollArea className="h-[500px]">
                <Table>
                  <TableHeader>
                    <TableRow className="bg-muted/50">
                      <TableHead className="w-[100px]">Niveau</TableHead>
                      <TableHead>Action</TableHead>
                      <TableHead>Utilisateur</TableHead>
                      <TableHead>IP</TableHead>
                      <TableHead className="w-[150px]">Date</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {logs.length === 0 ? (
                      <TableRow>
                        <TableCell colSpan={5} className="text-center py-10 text-muted-foreground">
                          Aucun log trouvé
                        </TableCell>
                      </TableRow>
                    ) : (
                      logs.map((log) => (
                        <TableRow key={log.id} className="hover:bg-muted/30">
                          <TableCell>
                            <Badge variant="outline" className={getLogLevelBadge(log.niveau)}>
                              {log.niveau}
                            </Badge>
                          </TableCell>
                          <TableCell>
                            <div>
                              <p className="font-medium text-sm">{log.action}</p>
                              {log.message && <p className="text-xs text-muted-foreground truncate max-w-[300px]">{log.message}</p>}
                            </div>
                          </TableCell>
                          <TableCell>
                            {log.prenom ? (
                              <div>
                                <p className="text-sm">{log.prenom} {log.nom}</p>
                                <p className="text-xs text-muted-foreground">{log.email}</p>
                              </div>
                            ) : (
                              <span className="text-muted-foreground text-sm">Système</span>
                            )}
                          </TableCell>
                          <TableCell className="font-mono text-xs">{log.ip_address}</TableCell>
                          <TableCell>
                            <div className="flex items-center gap-1 text-xs text-muted-foreground">
                              <Clock className="h-3 w-3" />
                              {formatDate(log.created_at)}
                            </div>
                          </TableCell>
                        </TableRow>
                      ))
                    )}
                  </TableBody>
                </Table>
              </ScrollArea>
            </CardContent>
          </Card>

          {/* Pagination simple */}
          {logsMeta.total > 20 && (
            <div className="flex items-center justify-center gap-2">
              <Button
                variant="outline"
                size="sm"
                disabled={logFilters.page === 1}
                onClick={() => setLogFilters(prev => ({ ...prev, page: prev.page - 1 }))}
              >
                Précédent
              </Button>
              <span className="text-sm text-muted-foreground">
                Page {logFilters.page}
              </span>
              <Button
                variant="outline"
                size="sm"
                onClick={() => setLogFilters(prev => ({ ...prev, page: prev.page + 1 }))}
              >
                Suivant
              </Button>
            </div>
          )}
        </TabsContent>

        {/* Onglet Sauvegardes */}
        <TabsContent value="sauvegardes" className="space-y-6 mt-6">
          <div className="flex items-center justify-between">
            <div>
              <h2 className="text-lg font-semibold">Sauvegardes de la base de données</h2>
              <p className="text-sm text-muted-foreground">Gérez les sauvegardes de vos données</p>
            </div>
            <AlertDialog>
              <AlertDialogTrigger asChild>
                <Button>
                  <HardDrive className="h-4 w-4 mr-2" />
                  Nouvelle sauvegarde
                </Button>
              </AlertDialogTrigger>
              <AlertDialogContent>
                <AlertDialogHeader>
                  <AlertDialogTitle>Créer une sauvegarde</AlertDialogTitle>
                  <AlertDialogDescription>
                    Cette action va créer une sauvegarde complète de la base de données. Cela peut prendre quelques minutes.
                  </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                  <AlertDialogCancel>Annuler</AlertDialogCancel>
                  <AlertDialogAction onClick={handleCreerSauvegarde} disabled={isCreatingSauvegarde}>
                    {isCreatingSauvegarde ? (
                      <><Loader2 className="h-4 w-4 mr-2 animate-spin" /> Création...</>
                    ) : (
                      "Créer"
                    )}
                  </AlertDialogAction>
                </AlertDialogFooter>
              </AlertDialogContent>
            </AlertDialog>
          </div>

          <Card>
            <CardContent className="p-0">
              <Table>
                <TableHeader>
                  <TableRow className="bg-muted/50">
                    <TableHead>Nom</TableHead>
                    <TableHead>Type</TableHead>
                    <TableHead>Taille</TableHead>
                    <TableHead>Statut</TableHead>
                    <TableHead>Date</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {sauvegardes.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={6} className="text-center py-10 text-muted-foreground">
                        Aucune sauvegarde disponible
                      </TableCell>
                    </TableRow>
                  ) : (
                    sauvegardes.map((sauvegarde) => (
                      <TableRow key={sauvegarde.id} className="hover:bg-muted/30">
                        <TableCell className="font-medium">{sauvegarde.nom}</TableCell>
                        <TableCell>
                          <Badge variant="outline">{sauvegarde.type}</Badge>
                        </TableCell>
                        <TableCell>{formatBytes(sauvegarde.taille)}</TableCell>
                        <TableCell>
                          <Badge variant="outline" className={sauvegarde.statut === "complete" ? "bg-green-500/10 text-green-600" : "bg-amber-500/10 text-amber-600"}>
                            {sauvegarde.statut === "complete" ? "Complète" : sauvegarde.statut}
                          </Badge>
                        </TableCell>
                        <TableCell>
                          <div className="flex items-center gap-1 text-sm text-muted-foreground">
                            <Clock className="h-3 w-3" />
                            {formatDate(sauvegarde.created_at)}
                          </div>
                        </TableCell>
                        <TableCell className="text-right">
                          <Button variant="ghost" size="sm">
                            <Download className="h-4 w-4 mr-1" />
                            Télécharger
                          </Button>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
