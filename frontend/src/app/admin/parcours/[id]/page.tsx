"use client";

import React, { useState, useEffect, useCallback } from "react";
import { useRouter, useParams } from "next/navigation";
import { apiClient } from "@/lib/api";
import {
  Loader2,
  ArrowLeft,
  Edit,
  Trash2,
  Globe,
  GlobeLock,
  Clock,
  Users,
  Layers,
  FileText,
  Video,
  Code,
  CheckCircle,
  BookOpen,
  Play,
  Award,
  BarChart3,
  Calendar,
  ChevronDown,
  ChevronRight,
  Copy,
  ExternalLink,
} from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { Progress } from "@/components/ui/progress";
import { toast } from "sonner";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from "@/components/ui/tabs";
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from "@/components/ui/collapsible";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip";

interface Lecon {
  id: string;
  titre: string;
  slug: string;
  type_contenu: string;
  duree_estimee_minutes: number;
  contenu: string;
  ordre: number;
  est_gratuit: boolean;
}

interface Module {
  id: string;
  titre: string;
  slug: string;
  description: string | null;
  ordre: number;
  duree_estimee_minutes: number;
  lecons: Lecon[];
  quiz?: {
    id: string;
    titre: string;
    questions_count: number;
  };
}

interface Parcours {
  id: string;
  titre: string;
  slug: string;
  description: string;
  technologie: string;
  difficulte: string;
  duree_estimee_heures: number;
  image_couverture: string | null;
  prerequis: string[] | string;
  objectifs: string[] | null;
  est_publie: boolean;
  ordre: number;
  created_at: string;
  updated_at: string;
  modules?: Module[];
}

interface Statistiques {
  total_modules: number;
  total_lecons: number;
  total_quiz: number;
  inscrits: number;
  taux_completion: number;
}

const DIFFICULTES: Record<string, { label: string; color: string }> = {
  debutant: { label: "Débutant", color: "bg-green-500" },
  intermediaire: { label: "Intermédiaire", color: "bg-yellow-500" },
  avance: { label: "Avancé", color: "bg-orange-500" },
  expert: { label: "Expert", color: "bg-red-500" },
};

const TYPE_ICONS: Record<string, React.ComponentType<{ className?: string }>> = {
  article: FileText,
  video: Video,
  exercice: CheckCircle,
  projet: Code,
};

export default function VoirParcoursPage() {
  const router = useRouter();
  const params = useParams();
  const id = params.id as string;

  const [parcours, setParcours] = useState<Parcours | null>(null);
  const [statistiques, setStatistiques] = useState<Statistiques | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [showDeleteDialog, setShowDeleteDialog] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);
  const [expandedModules, setExpandedModules] = useState<string[]>([]);

  const fetchParcours = useCallback(async () => {
    setIsLoading(true);
    try {
      const response = await apiClient.get(`/v1/admin/parcours/${id}`);
      setParcours(response.data.parcours);
      setStatistiques(response.data.statistiques);
      // Expand first module by default
      if (response.data.parcours.modules?.length > 0) {
        setExpandedModules([response.data.parcours.modules[0].id]);
      }
    } catch (error) {
      console.error("Erreur:", error);
      toast.error("Impossible de charger le parcours");
      router.push("/admin/parcours");
    } finally {
      setIsLoading(false);
    }
  }, [id, router]);

  useEffect(() => {
    fetchParcours();
  }, [fetchParcours]);

  const toggleModule = (moduleId: string) => {
    setExpandedModules((prev) =>
      prev.includes(moduleId)
        ? prev.filter((id) => id !== moduleId)
        : [...prev, moduleId]
    );
  };

  const handleTogglePublication = async () => {
    if (!parcours) return;

    try {
      const endpoint = parcours.est_publie
        ? `/v1/admin/parcours/${id}/depublier`
        : `/v1/admin/parcours/${id}/publier`;
      
      await apiClient.post(endpoint);
      toast.success(parcours.est_publie ? "Parcours dépublié" : "Parcours publié");
      fetchParcours();
    } catch (error) {
      console.error("Erreur:", error);
      toast.error("Erreur lors du changement de statut");
    }
  };

  const handleDelete = async () => {
    setIsDeleting(true);
    try {
      await apiClient.delete(`/v1/admin/parcours/${id}`);
      toast.success("Parcours supprimé");
      router.push("/admin/parcours");
    } catch (error: unknown) {
      console.error("Erreur:", error);
      const errorMessage = error instanceof Error ? error.message : "Erreur lors de la suppression";
      toast.error(errorMessage);
    } finally {
      setIsDeleting(false);
      setShowDeleteDialog(false);
    }
  };

  const handleDuplicate = async () => {
    try {
      await apiClient.post(`/v1/admin/parcours/${id}/dupliquer`);
      toast.success("Parcours dupliqué");
      router.push("/admin/parcours");
    } catch (error) {
      console.error("Erreur:", error);
      toast.error("Erreur lors de la duplication");
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString("fr-FR", {
      day: "numeric",
      month: "long",
      year: "numeric",
    });
  };

  const getPrerequisArray = (prerequis: string[] | string | null | undefined): string[] => {
    if (!prerequis) return [];
    if (Array.isArray(prerequis)) return prerequis;
    if (typeof prerequis === "string") {
      return prerequis.split(/[\n,]/).map(p => p.trim()).filter(p => p);
    }
    return [];
  };

  const calculerDureeTotale = () => {
    if (!parcours?.modules) return 0;
    return parcours.modules.reduce((total, module) => {
      return total + module.lecons.reduce((sum, lecon) => sum + lecon.duree_estimee_minutes, 0);
    }, 0);
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-96">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
      </div>
    );
  }

  if (!parcours) {
    return (
      <div className="text-center py-12">
        <p className="text-muted-foreground">Parcours non trouvé</p>
        <Button
          variant="outline"
          className="mt-4"
          onClick={() => router.push("/admin/parcours")}
        >
          Retour à la liste
        </Button>
      </div>
    );
  }

  const difficulte = DIFFICULTES[parcours.difficulte] || DIFFICULTES.debutant;

  return (
    <TooltipProvider>
      <div className="container mx-auto py-6 space-y-6">
        {/* En-tête */}
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div className="flex items-center gap-4">
            <Button
              variant="ghost"
              size="icon"
              onClick={() => router.push("/admin/parcours")}
            >
              <ArrowLeft className="h-5 w-5" />
            </Button>
            <div>
              <div className="flex items-center gap-2">
                <h1 className="text-2xl font-bold">{parcours.titre}</h1>
                <Badge variant={parcours.est_publie ? "default" : "secondary"}>
                  {parcours.est_publie ? (
                    <>
                      <Globe className="h-3 w-3 mr-1" />
                      Publié
                    </>
                  ) : (
                    <>
                      <GlobeLock className="h-3 w-3 mr-1" />
                      Brouillon
                    </>
                  )}
                </Badge>
              </div>
              <p className="text-muted-foreground">
                Créé le {formatDate(parcours.created_at)}
              </p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <Tooltip>
              <TooltipTrigger asChild>
                <Button variant="outline" size="icon" onClick={handleDuplicate}>
                  <Copy className="h-4 w-4" />
                </Button>
              </TooltipTrigger>
              <TooltipContent>Dupliquer</TooltipContent>
            </Tooltip>
            <Button variant="outline" onClick={handleTogglePublication}>
              {parcours.est_publie ? (
                <>
                  <GlobeLock className="h-4 w-4 mr-2" />
                  Dépublier
                </>
              ) : (
                <>
                  <Globe className="h-4 w-4 mr-2" />
                  Publier
                </>
              )}
            </Button>
            <Button
              variant="outline"
              onClick={() => router.push(`/admin/parcours/${id}/modifier`)}
            >
              <Edit className="h-4 w-4 mr-2" />
              Modifier
            </Button>
            <Button
              variant="destructive"
              onClick={() => setShowDeleteDialog(true)}
            >
              <Trash2 className="h-4 w-4 mr-2" />
              Supprimer
            </Button>
          </div>
        </div>

        {/* Statistiques */}
        <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
          <Card>
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <div className="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                  <Layers className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                  <div className="text-2xl font-bold">
                    {statistiques?.total_modules || parcours.modules?.length || 0}
                  </div>
                  <div className="text-sm text-muted-foreground">Modules</div>
                </div>
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <div className="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                  <FileText className="h-5 w-5 text-green-600 dark:text-green-400" />
                </div>
                <div>
                  <div className="text-2xl font-bold">
                    {statistiques?.total_lecons ||
                      parcours.modules?.reduce((sum, m) => sum + m.lecons.length, 0) ||
                      0}
                  </div>
                  <div className="text-sm text-muted-foreground">Leçons</div>
                </div>
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <div className="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                  <Clock className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                  <div className="text-2xl font-bold">
                    {Math.round(calculerDureeTotale() / 60) || parcours.duree_estimee_heures}h
                  </div>
                  <div className="text-sm text-muted-foreground">Durée</div>
                </div>
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <div className="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                  <Users className="h-5 w-5 text-orange-600 dark:text-orange-400" />
                </div>
                <div>
                  <div className="text-2xl font-bold">
                    {statistiques?.inscrits || 0}
                  </div>
                  <div className="text-sm text-muted-foreground">Inscrits</div>
                </div>
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <div className="p-2 bg-pink-100 dark:bg-pink-900/30 rounded-lg">
                  <Award className="h-5 w-5 text-pink-600 dark:text-pink-400" />
                </div>
                <div>
                  <div className="text-2xl font-bold">
                    {statistiques?.taux_completion || 0}%
                  </div>
                  <div className="text-sm text-muted-foreground">Complétion</div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Contenu principal */}
        <Tabs defaultValue="apercu" className="space-y-6">
          <TabsList>
            <TabsTrigger value="apercu">Aperçu</TabsTrigger>
            <TabsTrigger value="contenu">Contenu</TabsTrigger>
            <TabsTrigger value="statistiques">Statistiques</TabsTrigger>
          </TabsList>

          {/* Onglet Aperçu */}
          <TabsContent value="apercu" className="space-y-6">
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
              {/* Infos principales */}
              <Card className="lg:col-span-2">
                <CardHeader>
                  <CardTitle>Description</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <p className="text-muted-foreground leading-relaxed">
                    {parcours.description}
                  </p>
                  <div className="flex flex-wrap gap-2">
                    <Badge className="text-sm">{parcours.technologie}</Badge>
                    <Badge variant="outline" className="text-sm">
                      <div className={`w-2 h-2 rounded-full mr-2 ${difficulte.color}`} />
                      {difficulte.label}
                    </Badge>
                  </div>
                </CardContent>
              </Card>

              {/* Sidebar */}
              <div className="space-y-6">
                {/* Prérequis */}
                <Card>
                  <CardHeader className="pb-3">
                    <CardTitle className="text-base">Prérequis</CardTitle>
                  </CardHeader>
                  <CardContent>
                    {getPrerequisArray(parcours.prerequis).length > 0 ? (
                      <ul className="space-y-2">
                        {getPrerequisArray(parcours.prerequis).map((p, i) => (
                          <li key={i} className="flex items-start gap-2 text-sm">
                            <CheckCircle className="h-4 w-4 text-green-500 mt-0.5 shrink-0" />
                            <span>{p}</span>
                          </li>
                        ))}
                      </ul>
                    ) : (
                      <p className="text-sm text-muted-foreground">
                        Aucun prérequis spécifique
                      </p>
                    )}
                  </CardContent>
                </Card>

                {/* Objectifs */}
                <Card>
                  <CardHeader className="pb-3">
                    <CardTitle className="text-base">Objectifs</CardTitle>
                  </CardHeader>
                  <CardContent>
                    {parcours.objectifs && parcours.objectifs.length > 0 ? (
                      <ul className="space-y-2">
                        {parcours.objectifs.map((o, i) => (
                          <li key={i} className="flex items-start gap-2 text-sm">
                            <Award className="h-4 w-4 text-yellow-500 mt-0.5 shrink-0" />
                            <span>{o}</span>
                          </li>
                        ))}
                      </ul>
                    ) : (
                      <p className="text-sm text-muted-foreground">
                        Aucun objectif défini
                      </p>
                    )}
                  </CardContent>
                </Card>

                {/* Infos */}
                <Card>
                  <CardHeader className="pb-3">
                    <CardTitle className="text-base">Informations</CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-3">
                    <div className="flex items-center justify-between text-sm">
                      <span className="text-muted-foreground">Dernière mise à jour</span>
                      <span>{formatDate(parcours.updated_at)}</span>
                    </div>
                    <Separator />
                    <div className="flex items-center justify-between text-sm">
                      <span className="text-muted-foreground">ID</span>
                      <code className="text-xs bg-muted px-2 py-1 rounded">
                        {parcours.id.slice(0, 8)}...
                      </code>
                    </div>
                  </CardContent>
                </Card>
              </div>
            </div>
          </TabsContent>

          {/* Onglet Contenu */}
          <TabsContent value="contenu" className="space-y-4">
            <Card>
              <CardHeader>
                <div className="flex items-center justify-between">
                  <div>
                    <CardTitle>Structure du parcours</CardTitle>
                    <CardDescription>
                      {parcours.modules?.length || 0} modules,{" "}
                      {parcours.modules?.reduce((sum, m) => sum + m.lecons.length, 0) || 0} leçons
                    </CardDescription>
                  </div>
                  <Button
                    variant="outline"
                    onClick={() => router.push(`/admin/parcours/${id}/modifier`)}
                  >
                    <Edit className="h-4 w-4 mr-2" />
                    Modifier le contenu
                  </Button>
                </div>
              </CardHeader>
              <CardContent>
                {!parcours.modules || parcours.modules.length === 0 ? (
                  <div className="text-center py-12">
                    <BookOpen className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                    <h3 className="text-lg font-medium">Aucun module</h3>
                    <p className="text-muted-foreground mb-4">
                      Ce parcours n&apos;a pas encore de contenu
                    </p>
                    <Button onClick={() => router.push(`/admin/parcours/${id}/modifier`)}>
                      Ajouter du contenu
                    </Button>
                  </div>
                ) : (
                  <div className="space-y-3">
                    {parcours.modules.map((module, index) => (
                      <Collapsible
                        key={module.id}
                        open={expandedModules.includes(module.id)}
                        onOpenChange={() => toggleModule(module.id)}
                      >
                        <div className="border rounded-lg">
                          <CollapsibleTrigger className="w-full">
                            <div className="flex items-center justify-between p-4 hover:bg-muted/50 transition-colors">
                              <div className="flex items-center gap-3">
                                {expandedModules.includes(module.id) ? (
                                  <ChevronDown className="h-5 w-5 text-muted-foreground" />
                                ) : (
                                  <ChevronRight className="h-5 w-5 text-muted-foreground" />
                                )}
                                <div className="flex items-center gap-3">
                                  <div className="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-sm font-medium">
                                    {index + 1}
                                  </div>
                                  <div className="text-left">
                                    <div className="font-medium">{module.titre}</div>
                                    {module.description && (
                                      <div className="text-sm text-muted-foreground">
                                        {module.description}
                                      </div>
                                    )}
                                  </div>
                                </div>
                              </div>
                              <div className="flex items-center gap-3">
                                <Badge variant="secondary">
                                  {module.lecons.length} leçon(s)
                                </Badge>
                                <Badge variant="outline">
                                  {module.duree_estimee_minutes || 
                                    module.lecons.reduce((sum, l) => sum + l.duree_estimee_minutes, 0)} min
                                </Badge>
                              </div>
                            </div>
                          </CollapsibleTrigger>
                          <CollapsibleContent>
                            <Separator />
                            <div className="p-4 bg-muted/30">
                              {module.lecons.length === 0 ? (
                                <p className="text-sm text-muted-foreground text-center py-4">
                                  Aucune leçon dans ce module
                                </p>
                              ) : (
                                <div className="space-y-2">
                                  {module.lecons.map((lecon, leconIndex) => {
                                    const Icon = TYPE_ICONS[lecon.type_contenu] || FileText;
                                    return (
                                      <div
                                        key={lecon.id}
                                        className="flex items-center gap-3 p-3 bg-background rounded-lg"
                                      >
                                        <span className="text-sm text-muted-foreground w-6">
                                          {leconIndex + 1}.
                                        </span>
                                        <Icon className="h-4 w-4 text-muted-foreground" />
                                        <div className="flex-1">
                                          <div className="font-medium text-sm">
                                            {lecon.titre}
                                          </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                          {lecon.est_gratuit && (
                                            <Badge variant="outline" className="text-xs">
                                              Gratuit
                                            </Badge>
                                          )}
                                          <Badge variant="secondary" className="text-xs">
                                            {lecon.duree_estimee_minutes} min
                                          </Badge>
                                        </div>
                                      </div>
                                    );
                                  })}
                                </div>
                              )}
                              {module.quiz && (
                                <div className="mt-3 p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg flex items-center gap-3">
                                  <Award className="h-4 w-4 text-purple-600 dark:text-purple-400" />
                                  <div className="flex-1">
                                    <div className="font-medium text-sm">
                                      Quiz: {module.quiz.titre}
                                    </div>
                                    <div className="text-xs text-muted-foreground">
                                      {module.quiz.questions_count} questions
                                    </div>
                                  </div>
                                </div>
                              )}
                            </div>
                          </CollapsibleContent>
                        </div>
                      </Collapsible>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>

          {/* Onglet Statistiques */}
          <TabsContent value="statistiques" className="space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <Card>
                <CardHeader>
                  <CardTitle>Progression des inscrits</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">
                      Taux de complétion moyen
                    </span>
                    <span className="font-bold">
                      {statistiques?.taux_completion || 0}%
                    </span>
                  </div>
                  <Progress value={statistiques?.taux_completion || 0} />
                  <div className="grid grid-cols-2 gap-4 pt-4">
                    <div className="text-center p-4 bg-muted rounded-lg">
                      <div className="text-2xl font-bold">
                        {statistiques?.inscrits || 0}
                      </div>
                      <div className="text-sm text-muted-foreground">
                        Utilisateurs inscrits
                      </div>
                    </div>
                    <div className="text-center p-4 bg-muted rounded-lg">
                      <div className="text-2xl font-bold">
                        {Math.round(
                          ((statistiques?.inscrits || 0) *
                            (statistiques?.taux_completion || 0)) /
                            100
                        )}
                      </div>
                      <div className="text-sm text-muted-foreground">
                        Ont terminé
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader>
                  <CardTitle>Contenu</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    <div className="flex items-center justify-between p-3 bg-muted rounded-lg">
                      <div className="flex items-center gap-3">
                        <Layers className="h-5 w-5 text-muted-foreground" />
                        <span>Modules</span>
                      </div>
                      <span className="font-bold">
                        {statistiques?.total_modules || parcours.modules?.length || 0}
                      </span>
                    </div>
                    <div className="flex items-center justify-between p-3 bg-muted rounded-lg">
                      <div className="flex items-center gap-3">
                        <FileText className="h-5 w-5 text-muted-foreground" />
                        <span>Leçons</span>
                      </div>
                      <span className="font-bold">
                        {statistiques?.total_lecons ||
                          parcours.modules?.reduce((s, m) => s + m.lecons.length, 0) ||
                          0}
                      </span>
                    </div>
                    <div className="flex items-center justify-between p-3 bg-muted rounded-lg">
                      <div className="flex items-center gap-3">
                        <Award className="h-5 w-5 text-muted-foreground" />
                        <span>Quiz</span>
                      </div>
                      <span className="font-bold">
                        {statistiques?.total_quiz || 0}
                      </span>
                    </div>
                    <div className="flex items-center justify-between p-3 bg-muted rounded-lg">
                      <div className="flex items-center gap-3">
                        <Clock className="h-5 w-5 text-muted-foreground" />
                        <span>Durée totale</span>
                      </div>
                      <span className="font-bold">
                        {Math.round(calculerDureeTotale() / 60) || parcours.duree_estimee_heures}h
                      </span>
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
          </TabsContent>
        </Tabs>

        {/* Dialog de suppression */}
        <AlertDialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
          <AlertDialogContent>
            <AlertDialogHeader>
              <AlertDialogTitle>Supprimer ce parcours ?</AlertDialogTitle>
              <AlertDialogDescription>
                Cette action est irréversible. Le parcours &quot;{parcours.titre}&quot; et
                tout son contenu (modules, leçons, quiz) seront définitivement
                supprimés.
                {statistiques && statistiques.inscrits > 0 && (
                  <span className="block mt-2 text-destructive">
                    Attention : {statistiques.inscrits} utilisateur(s) sont inscrits
                    à ce parcours.
                  </span>
                )}
              </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
              <AlertDialogCancel>Annuler</AlertDialogCancel>
              <AlertDialogAction
                onClick={handleDelete}
                className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                disabled={isDeleting}
              >
                {isDeleting ? (
                  <Loader2 className="h-4 w-4 animate-spin" />
                ) : (
                  "Supprimer"
                )}
              </AlertDialogAction>
            </AlertDialogFooter>
          </AlertDialogContent>
        </AlertDialog>
      </div>
    </TooltipProvider>
  );
}
