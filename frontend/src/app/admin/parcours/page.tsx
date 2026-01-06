"use client";

import React, { useState, useEffect, useCallback } from "react";
import { useRouter } from "next/navigation";
import { apiClient } from "@/lib/api";
import {
  Loader2,
  Plus,
  Search,
  BookOpen,
  Eye,
  Edit,
  Trash2,
  Copy,
  MoreHorizontal,
  Globe,
  GlobeLock,
  Clock,
  Users,
  Layers,
  FileText,
  Filter,
  ChevronRight,
  ChevronDown,
} from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Switch } from "@/components/ui/switch";
import { ScrollArea } from "@/components/ui/scroll-area";
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationLink,
  PaginationNext,
  PaginationPrevious,
} from "@/components/ui/pagination";
import { toast } from "sonner";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip";
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from "@/components/ui/collapsible";

interface Module {
  id: string;
  titre: string;
  description: string | null;
  ordre: number;
  lecons: Lecon[];
}

interface Lecon {
  id: string;
  titre: string;
  type: string;
  duree_estimee_minutes: number;
  ordre: number;
}

interface Parcours {
  id: string;
  titre: string;
  description: string;
  technologie: string;
  difficulte: string;
  duree_estimee_heures: number;
  image_couverture: string | null;
  prerequis: string[];
  objectifs: string[];
  est_publie: boolean;
  ordre: number;
  created_at: string;
  updated_at: string;
  createur?: {
    id: string;
    prenom: string;
    nom: string;
  };
  modules?: Module[];
  utilisateurs_inscrits_count?: number;
}

interface Statistiques {
  total: number;
  publies: number;
  brouillons: number;
  par_difficulte: Record<string, number>;
}

interface ApiResponse {
  success: boolean;
  data: {
    parcours: Parcours[];
    statistiques: Statistiques;
    meta: {
      total: number;
      par_page: number;
      page_courante: number;
      derniere_page: number;
    };
  };
}

const DIFFICULTES = [
  { value: "debutant", label: "Débutant", color: "bg-green-500" },
  { value: "intermediaire", label: "Intermédiaire", color: "bg-yellow-500" },
  { value: "avance", label: "Avancé", color: "bg-orange-500" },
  { value: "expert", label: "Expert", color: "bg-red-500" },
];

const TECHNOLOGIES = [
  "JavaScript", "TypeScript", "Python", "PHP", "Java", "C#", "Go",
  "React", "Vue.js", "Angular", "Node.js", "Laravel", "Django", "Spring"
];

export default function ParcoursPage() {
  const router = useRouter();
  const [parcours, setParcours] = useState<Parcours[]>([]);
  const [statistiques, setStatistiques] = useState<Statistiques | null>(null);
  const [meta, setMeta] = useState({
    total: 0,
    par_page: 10,
    page_courante: 1,
    derniere_page: 1,
  });
  const [isLoading, setIsLoading] = useState(true);
  const [search, setSearch] = useState("");
  const [filterDifficulte, setFilterDifficulte] = useState<string>("all");
  const [filterStatut, setFilterStatut] = useState<string>("all");
  const [filterTechnologie, setFilterTechnologie] = useState<string>("all");

  // Dialogs
  const [showCreateDialog, setShowCreateDialog] = useState(false);
  const [showEditDialog, setShowEditDialog] = useState(false);
  const [showDeleteDialog, setShowDeleteDialog] = useState(false);
  const [showViewDialog, setShowViewDialog] = useState(false);
  const [selectedParcours, setSelectedParcours] = useState<Parcours | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Form state
  const [formData, setFormData] = useState({
    titre: "",
    description: "",
    technologie: "",
    difficulte: "debutant",
    duree_estimee_heures: 10,
    est_publie: false,
    prerequis: "",
    objectifs: "",
  });

  // Expanded modules in view
  const [expandedModules, setExpandedModules] = useState<string[]>([]);

  const fetchParcours = useCallback(async () => {
    setIsLoading(true);
    try {
      const params = new URLSearchParams({
        page: meta.page_courante.toString(),
        per_page: meta.par_page.toString(),
      });

      if (search) params.append("recherche", search);
      if (filterDifficulte !== "all") params.append("difficulte", filterDifficulte);
      if (filterStatut !== "all") params.append("est_publie", filterStatut === "publie" ? "true" : "false");
      if (filterTechnologie !== "all") params.append("technologie", filterTechnologie);

      const response = await apiClient.get<ApiResponse>(`/v1/admin/parcours?${params}`);
      setParcours(response.data.parcours);
      setStatistiques(response.data.statistiques);
      setMeta(response.data.meta);
    } catch (error) {
      console.error("Erreur:", error);
      toast.error("Erreur lors du chargement des parcours");
    } finally {
      setIsLoading(false);
    }
  }, [meta.page_courante, meta.par_page, search, filterDifficulte, filterStatut, filterTechnologie]);

  useEffect(() => {
    fetchParcours();
  }, [fetchParcours]);

  const handleCreate = async () => {
    setIsSubmitting(true);
    try {
      await apiClient.post("/v1/admin/parcours", {
        ...formData,
        prerequis: formData.prerequis.split("\n").filter(p => p.trim()),
        objectifs: formData.objectifs.split("\n").filter(o => o.trim()),
      });
      toast.success("Parcours créé avec succès");
      setShowCreateDialog(false);
      resetForm();
      fetchParcours();
    } catch (error) {
      console.error("Erreur:", error);
      toast.error("Erreur lors de la création");
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleUpdate = async () => {
    if (!selectedParcours) return;
    setIsSubmitting(true);
    try {
      await apiClient.put(`/v1/admin/parcours/${selectedParcours.id}`, {
        ...formData,
        prerequis: formData.prerequis.split("\n").filter(p => p.trim()),
        objectifs: formData.objectifs.split("\n").filter(o => o.trim()),
      });
      toast.success("Parcours mis à jour avec succès");
      setShowEditDialog(false);
      fetchParcours();
    } catch (error) {
      console.error("Erreur:", error);
      toast.error("Erreur lors de la mise à jour");
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleDelete = async () => {
    if (!selectedParcours) return;
    setIsSubmitting(true);
    try {
      await apiClient.delete(`/v1/admin/parcours/${selectedParcours.id}`);
      toast.success("Parcours supprimé avec succès");
      setShowDeleteDialog(false);
      fetchParcours();
    } catch (error) {
      console.error("Erreur:", error);
      toast.error("Erreur lors de la suppression");
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleTogglePublication = async (p: Parcours) => {
    try {
      await apiClient.post(`/v1/admin/parcours/${p.id}/toggle-publication`);
      toast.success(p.est_publie ? "Parcours dépublié" : "Parcours publié");
      fetchParcours();
    } catch (error) {
      console.error("Erreur:", error);
      toast.error("Erreur lors du changement de statut");
    }
  };

  const handleDuplicate = async (p: Parcours) => {
    try {
      await apiClient.post(`/v1/admin/parcours/${p.id}/dupliquer`);
      toast.success("Parcours dupliqué avec succès");
      fetchParcours();
    } catch (error) {
      console.error("Erreur:", error);
      toast.error("Erreur lors de la duplication");
    }
  };

  const openEditDialog = (p: Parcours) => {
    setSelectedParcours(p);
    setFormData({
      titre: p.titre,
      description: p.description,
      technologie: p.technologie,
      difficulte: p.difficulte,
      duree_estimee_heures: p.duree_estimee_heures,
      est_publie: p.est_publie,
      prerequis: (p.prerequis || []).join("\n"),
      objectifs: (p.objectifs || []).join("\n"),
    });
    setShowEditDialog(true);
  };

  const openViewDialog = async (p: Parcours) => {
    try {
      const response = await apiClient.get<{ success: boolean; data: { parcours: Parcours } }>(`/v1/admin/parcours/${p.id}`);
      setSelectedParcours(response.data.parcours);
      setShowViewDialog(true);
    } catch (error) {
      console.error("Erreur:", error);
      toast.error("Erreur lors du chargement des détails");
    }
  };

  const resetForm = () => {
    setFormData({
      titre: "",
      description: "",
      technologie: "",
      difficulte: "debutant",
      duree_estimee_heures: 10,
      est_publie: false,
      prerequis: "",
      objectifs: "",
    });
  };

  const getDifficulteColor = (difficulte: string) => {
    const d = DIFFICULTES.find(d => d.value === difficulte);
    return d?.color || "bg-gray-500";
  };

  const getDifficulteLabel = (difficulte: string) => {
    const d = DIFFICULTES.find(d => d.value === difficulte);
    return d?.label || difficulte;
  };

  const toggleModule = (moduleId: string) => {
    setExpandedModules(prev => 
      prev.includes(moduleId) 
        ? prev.filter(id => id !== moduleId)
        : [...prev, moduleId]
    );
  };

  return (
    <TooltipProvider>
      <div className="space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold">Gestion des parcours</h1>
            <p className="text-muted-foreground">Créez et gérez les parcours d&apos;apprentissage</p>
          </div>
          <Button onClick={() => router.push("/admin/parcours/nouveau")}>
            <Plus className="h-4 w-4 mr-2" />
            Nouveau parcours
          </Button>
        </div>

        {/* Statistiques */}
        <div className="grid gap-3 grid-cols-2 lg:grid-cols-4">
          <Card className="border-l-4 border-l-blue-500 py-3">
            <CardContent className="flex items-center gap-3 p-0 px-4">
              <div className="p-2 rounded-full bg-blue-500/10">
                <BookOpen className="h-4 w-4 text-blue-500" />
              </div>
              <div>
                <p className="text-2xl font-bold">{statistiques?.total || 0}</p>
                <p className="text-xs text-muted-foreground">Total parcours</p>
              </div>
            </CardContent>
          </Card>
          <Card className="border-l-4 border-l-green-500 py-3">
            <CardContent className="flex items-center gap-3 p-0 px-4">
              <div className="p-2 rounded-full bg-green-500/10">
                <Globe className="h-4 w-4 text-green-500" />
              </div>
              <div>
                <p className="text-2xl font-bold">{statistiques?.publies || 0}</p>
                <p className="text-xs text-muted-foreground">Publiés</p>
              </div>
            </CardContent>
          </Card>
          <Card className="border-l-4 border-l-amber-500 py-3">
            <CardContent className="flex items-center gap-3 p-0 px-4">
              <div className="p-2 rounded-full bg-amber-500/10">
                <GlobeLock className="h-4 w-4 text-amber-500" />
              </div>
              <div>
                <p className="text-2xl font-bold">{statistiques?.brouillons || 0}</p>
                <p className="text-xs text-muted-foreground">Brouillons</p>
              </div>
            </CardContent>
          </Card>
          <Card className="border-l-4 border-l-purple-500 py-3">
            <CardContent className="flex items-center gap-3 p-0 px-4">
              <div className="p-2 rounded-full bg-purple-500/10">
                <Layers className="h-4 w-4 text-purple-500" />
              </div>
              <div>
                <p className="text-2xl font-bold">
                  {Object.values(statistiques?.par_difficulte || {}).reduce((a, b) => a + b, 0)}
                </p>
                <p className="text-xs text-muted-foreground">Niveaux</p>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Filtres */}
        <div className="flex flex-wrap items-center gap-3">
          <div className="relative flex-1 min-w-[200px] max-w-sm">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input
              placeholder="Rechercher un parcours..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="pl-9"
            />
          </div>
          <Select value={filterStatut} onValueChange={setFilterStatut}>
            <SelectTrigger className="w-[140px]">
              <SelectValue placeholder="Statut" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Tous</SelectItem>
              <SelectItem value="publie">Publiés</SelectItem>
              <SelectItem value="brouillon">Brouillons</SelectItem>
            </SelectContent>
          </Select>
          <Select value={filterDifficulte} onValueChange={setFilterDifficulte}>
            <SelectTrigger className="w-[150px]">
              <SelectValue placeholder="Difficulté" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Toutes</SelectItem>
              {DIFFICULTES.map(d => (
                <SelectItem key={d.value} value={d.value}>{d.label}</SelectItem>
              ))}
            </SelectContent>
          </Select>
          <Select value={filterTechnologie} onValueChange={setFilterTechnologie}>
            <SelectTrigger className="w-[150px]">
              <SelectValue placeholder="Technologie" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Toutes</SelectItem>
              {TECHNOLOGIES.map(t => (
                <SelectItem key={t} value={t}>{t}</SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        {/* Table */}
        <Card>
          <CardContent className="p-0">
            {isLoading ? (
              <div className="flex items-center justify-center py-20">
                <Loader2 className="h-8 w-8 animate-spin" />
              </div>
            ) : (
              <Table>
                <TableHeader>
                  <TableRow className="bg-muted/50">
                    <TableHead className="font-semibold">Parcours</TableHead>
                    <TableHead className="font-semibold">Technologie</TableHead>
                    <TableHead className="font-semibold">Difficulté</TableHead>
                    <TableHead className="font-semibold">Durée</TableHead>
                    <TableHead className="font-semibold">Statut</TableHead>
                    <TableHead className="font-semibold text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {parcours.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={6} className="text-center py-10 text-muted-foreground">
                        Aucun parcours trouvé
                      </TableCell>
                    </TableRow>
                  ) : (
                    parcours.map((p) => (
                      <TableRow key={p.id} className="group hover:bg-muted/30">
                        <TableCell>
                          <div className="flex items-center gap-3">
                            <div className="w-10 h-10 rounded-lg bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center">
                              <BookOpen className="h-5 w-5 text-primary" />
                            </div>
                            <div>
                              <p className="font-medium">{p.titre}</p>
                              <p className="text-xs text-muted-foreground line-clamp-1 max-w-[250px]">
                                {p.description}
                              </p>
                            </div>
                          </div>
                        </TableCell>
                        <TableCell>
                          <Badge variant="outline" className="font-normal">
                            {p.technologie}
                          </Badge>
                        </TableCell>
                        <TableCell>
                          <div className="flex items-center gap-2">
                            <div className={`w-2 h-2 rounded-full ${getDifficulteColor(p.difficulte)}`} />
                            <span className="text-sm">{getDifficulteLabel(p.difficulte)}</span>
                          </div>
                        </TableCell>
                        <TableCell>
                          <div className="flex items-center gap-1 text-sm text-muted-foreground">
                            <Clock className="h-3.5 w-3.5" />
                            {p.duree_estimee_heures}h
                          </div>
                        </TableCell>
                        <TableCell>
                          {p.est_publie ? (
                            <Badge className="bg-green-500/10 text-green-600 border-green-500/30">
                              <Globe className="h-3 w-3 mr-1" /> Publié
                            </Badge>
                          ) : (
                            <Badge variant="outline" className="text-muted-foreground">
                              <GlobeLock className="h-3 w-3 mr-1" /> Brouillon
                            </Badge>
                          )}
                        </TableCell>
                        <TableCell className="text-right">
                          <div className="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <Tooltip>
                              <TooltipTrigger asChild>
                                <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => router.push(`/admin/parcours/${p.id}`)}>
                                  <Eye className="h-4 w-4" />
                                </Button>
                              </TooltipTrigger>
                              <TooltipContent>Voir détails</TooltipContent>
                            </Tooltip>
                            <Tooltip>
                              <TooltipTrigger asChild>
                                <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => router.push(`/admin/parcours/${p.id}/modifier`)}>
                                  <Edit className="h-4 w-4" />
                                </Button>
                              </TooltipTrigger>
                              <TooltipContent>Modifier</TooltipContent>
                            </Tooltip>
                            <DropdownMenu>
                              <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="icon" className="h-8 w-8">
                                  <MoreHorizontal className="h-4 w-4" />
                                </Button>
                              </DropdownMenuTrigger>
                              <DropdownMenuContent align="end">
                                <DropdownMenuItem onClick={() => handleTogglePublication(p)}>
                                  {p.est_publie ? (
                                    <><GlobeLock className="h-4 w-4 mr-2" /> Dépublier</>
                                  ) : (
                                    <><Globe className="h-4 w-4 mr-2" /> Publier</>
                                  )}
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={() => handleDuplicate(p)}>
                                  <Copy className="h-4 w-4 mr-2" /> Dupliquer
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem 
                                  className="text-destructive"
                                  onClick={() => { setSelectedParcours(p); setShowDeleteDialog(true); }}
                                >
                                  <Trash2 className="h-4 w-4 mr-2" /> Supprimer
                                </DropdownMenuItem>
                              </DropdownMenuContent>
                            </DropdownMenu>
                          </div>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            )}
          </CardContent>
        </Card>

        {/* Pagination */}
        {meta.derniere_page > 1 && (
          <Pagination>
            <PaginationContent>
              <PaginationItem>
                <PaginationPrevious 
                  onClick={() => setMeta(prev => ({ ...prev, page_courante: Math.max(1, prev.page_courante - 1) }))}
                  className={meta.page_courante === 1 ? "pointer-events-none opacity-50" : "cursor-pointer"}
                />
              </PaginationItem>
              {Array.from({ length: Math.min(5, meta.derniere_page) }, (_, i) => {
                const page = i + 1;
                return (
                  <PaginationItem key={page}>
                    <PaginationLink
                      onClick={() => setMeta(prev => ({ ...prev, page_courante: page }))}
                      isActive={page === meta.page_courante}
                      className="cursor-pointer"
                    >
                      {page}
                    </PaginationLink>
                  </PaginationItem>
                );
              })}
              <PaginationItem>
                <PaginationNext 
                  onClick={() => setMeta(prev => ({ ...prev, page_courante: Math.min(meta.derniere_page, prev.page_courante + 1) }))}
                  className={meta.page_courante === meta.derniere_page ? "pointer-events-none opacity-50" : "cursor-pointer"}
                />
              </PaginationItem>
            </PaginationContent>
          </Pagination>
        )}

        {/* Dialog Création */}
        <Dialog open={showCreateDialog} onOpenChange={setShowCreateDialog}>
          <DialogContent className="max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
            <DialogHeader>
              <DialogTitle>Créer un nouveau parcours</DialogTitle>
              <DialogDescription>Remplissez les informations du parcours</DialogDescription>
            </DialogHeader>
            <ScrollArea className="flex-1 pr-4">
              <div className="space-y-4 py-4">
                <div className="space-y-2">
                  <Label htmlFor="titre">Titre *</Label>
                  <Input
                    id="titre"
                    value={formData.titre}
                    onChange={(e) => setFormData({ ...formData, titre: e.target.value })}
                    placeholder="Ex: Introduction à React"
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="description">Description *</Label>
                  <Textarea
                    id="description"
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                    placeholder="Décrivez le contenu du parcours..."
                    rows={3}
                  />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="technologie">Technologie *</Label>
                    <Select value={formData.technologie} onValueChange={(v) => setFormData({ ...formData, technologie: v })}>
                      <SelectTrigger>
                        <SelectValue placeholder="Sélectionner" />
                      </SelectTrigger>
                      <SelectContent>
                        {TECHNOLOGIES.map(t => (
                          <SelectItem key={t} value={t}>{t}</SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="difficulte">Difficulté *</Label>
                    <Select value={formData.difficulte} onValueChange={(v) => setFormData({ ...formData, difficulte: v })}>
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {DIFFICULTES.map(d => (
                          <SelectItem key={d.value} value={d.value}>{d.label}</SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="duree">Durée estimée (heures)</Label>
                  <Input
                    id="duree"
                    type="number"
                    min={1}
                    value={formData.duree_estimee_heures}
                    onChange={(e) => setFormData({ ...formData, duree_estimee_heures: parseInt(e.target.value) || 0 })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="prerequis">Prérequis (un par ligne)</Label>
                  <Textarea
                    id="prerequis"
                    value={formData.prerequis}
                    onChange={(e) => setFormData({ ...formData, prerequis: e.target.value })}
                    placeholder="Connaissances de base en HTML/CSS&#10;Notions de JavaScript"
                    rows={3}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="objectifs">Objectifs (un par ligne)</Label>
                  <Textarea
                    id="objectifs"
                    value={formData.objectifs}
                    onChange={(e) => setFormData({ ...formData, objectifs: e.target.value })}
                    placeholder="Maîtriser les hooks React&#10;Créer des composants réutilisables"
                    rows={3}
                  />
                </div>
                <div className="flex items-center gap-2">
                  <Switch
                    id="est_publie"
                    checked={formData.est_publie}
                    onCheckedChange={(v) => setFormData({ ...formData, est_publie: v })}
                  />
                  <Label htmlFor="est_publie">Publier immédiatement</Label>
                </div>
              </div>
            </ScrollArea>
            <DialogFooter>
              <Button variant="outline" onClick={() => setShowCreateDialog(false)}>Annuler</Button>
              <Button onClick={handleCreate} disabled={isSubmitting || !formData.titre || !formData.description || !formData.technologie}>
                {isSubmitting && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                Créer
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>

        {/* Dialog Modification */}
        <Dialog open={showEditDialog} onOpenChange={setShowEditDialog}>
          <DialogContent className="max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
            <DialogHeader>
              <DialogTitle>Modifier le parcours</DialogTitle>
              <DialogDescription>Modifiez les informations du parcours</DialogDescription>
            </DialogHeader>
            <ScrollArea className="flex-1 pr-4">
              <div className="space-y-4 py-4">
                <div className="space-y-2">
                  <Label htmlFor="edit-titre">Titre *</Label>
                  <Input
                    id="edit-titre"
                    value={formData.titre}
                    onChange={(e) => setFormData({ ...formData, titre: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="edit-description">Description *</Label>
                  <Textarea
                    id="edit-description"
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                    rows={3}
                  />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="edit-technologie">Technologie *</Label>
                    <Select value={formData.technologie} onValueChange={(v) => setFormData({ ...formData, technologie: v })}>
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {TECHNOLOGIES.map(t => (
                          <SelectItem key={t} value={t}>{t}</SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="edit-difficulte">Difficulté *</Label>
                    <Select value={formData.difficulte} onValueChange={(v) => setFormData({ ...formData, difficulte: v })}>
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {DIFFICULTES.map(d => (
                          <SelectItem key={d.value} value={d.value}>{d.label}</SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="edit-duree">Durée estimée (heures)</Label>
                  <Input
                    id="edit-duree"
                    type="number"
                    min={1}
                    value={formData.duree_estimee_heures}
                    onChange={(e) => setFormData({ ...formData, duree_estimee_heures: parseInt(e.target.value) || 0 })}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="edit-prerequis">Prérequis (un par ligne)</Label>
                  <Textarea
                    id="edit-prerequis"
                    value={formData.prerequis}
                    onChange={(e) => setFormData({ ...formData, prerequis: e.target.value })}
                    rows={3}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="edit-objectifs">Objectifs (un par ligne)</Label>
                  <Textarea
                    id="edit-objectifs"
                    value={formData.objectifs}
                    onChange={(e) => setFormData({ ...formData, objectifs: e.target.value })}
                    rows={3}
                  />
                </div>
                <div className="flex items-center gap-2">
                  <Switch
                    id="edit-est_publie"
                    checked={formData.est_publie}
                    onCheckedChange={(v) => setFormData({ ...formData, est_publie: v })}
                  />
                  <Label htmlFor="edit-est_publie">Publié</Label>
                </div>
              </div>
            </ScrollArea>
            <DialogFooter>
              <Button variant="outline" onClick={() => setShowEditDialog(false)}>Annuler</Button>
              <Button onClick={handleUpdate} disabled={isSubmitting}>
                {isSubmitting && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                Enregistrer
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>

        {/* Dialog Suppression */}
        <Dialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Supprimer le parcours</DialogTitle>
              <DialogDescription>
                Êtes-vous sûr de vouloir supprimer le parcours &quot;{selectedParcours?.titre}&quot; ?
                Cette action est irréversible.
              </DialogDescription>
            </DialogHeader>
            <DialogFooter>
              <Button variant="outline" onClick={() => setShowDeleteDialog(false)}>Annuler</Button>
              <Button variant="destructive" onClick={handleDelete} disabled={isSubmitting}>
                {isSubmitting && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                Supprimer
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>

        {/* Dialog Vue détaillée */}
        <Dialog open={showViewDialog} onOpenChange={setShowViewDialog}>
          <DialogContent className="max-w-3xl max-h-[90vh] overflow-hidden flex flex-col">
            <DialogHeader>
              <DialogTitle className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-lg bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center">
                  <BookOpen className="h-5 w-5 text-primary" />
                </div>
                {selectedParcours?.titre}
              </DialogTitle>
              <DialogDescription>{selectedParcours?.description}</DialogDescription>
            </DialogHeader>
            <ScrollArea className="flex-1 pr-4">
              <div className="space-y-6 py-4">
                {/* Infos */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                  <div className="text-center p-3 rounded-lg bg-muted/50">
                    <p className="text-lg font-bold">{selectedParcours?.technologie}</p>
                    <p className="text-xs text-muted-foreground">Technologie</p>
                  </div>
                  <div className="text-center p-3 rounded-lg bg-muted/50">
                    <p className="text-lg font-bold">{getDifficulteLabel(selectedParcours?.difficulte || "")}</p>
                    <p className="text-xs text-muted-foreground">Difficulté</p>
                  </div>
                  <div className="text-center p-3 rounded-lg bg-muted/50">
                    <p className="text-lg font-bold">{selectedParcours?.duree_estimee_heures}h</p>
                    <p className="text-xs text-muted-foreground">Durée</p>
                  </div>
                  <div className="text-center p-3 rounded-lg bg-muted/50">
                    <p className="text-lg font-bold">{selectedParcours?.modules?.length || 0}</p>
                    <p className="text-xs text-muted-foreground">Modules</p>
                  </div>
                </div>

                {/* Prérequis et objectifs */}
                <div className="grid md:grid-cols-2 gap-4">
                  {selectedParcours?.prerequis && selectedParcours.prerequis.length > 0 && (
                    <Card>
                      <CardHeader className="py-3">
                        <CardTitle className="text-sm">Prérequis</CardTitle>
                      </CardHeader>
                      <CardContent className="pt-0">
                        <ul className="space-y-1 text-sm">
                          {selectedParcours.prerequis.map((p, i) => (
                            <li key={i} className="flex items-start gap-2">
                              <span className="text-muted-foreground">•</span>
                              {p}
                            </li>
                          ))}
                        </ul>
                      </CardContent>
                    </Card>
                  )}
                  {selectedParcours?.objectifs && selectedParcours.objectifs.length > 0 && (
                    <Card>
                      <CardHeader className="py-3">
                        <CardTitle className="text-sm">Objectifs</CardTitle>
                      </CardHeader>
                      <CardContent className="pt-0">
                        <ul className="space-y-1 text-sm">
                          {selectedParcours.objectifs.map((o, i) => (
                            <li key={i} className="flex items-start gap-2">
                              <span className="text-green-500">✓</span>
                              {o}
                            </li>
                          ))}
                        </ul>
                      </CardContent>
                    </Card>
                  )}
                </div>

                {/* Modules */}
                {selectedParcours?.modules && selectedParcours.modules.length > 0 && (
                  <div className="space-y-3">
                    <h3 className="font-semibold flex items-center gap-2">
                      <Layers className="h-4 w-4" />
                      Modules ({selectedParcours.modules.length})
                    </h3>
                    <div className="space-y-2">
                      {selectedParcours.modules.map((module) => (
                        <Collapsible key={module.id} open={expandedModules.includes(module.id)}>
                          <Card>
                            <CollapsibleTrigger asChild>
                              <CardHeader 
                                className="py-3 cursor-pointer hover:bg-muted/30 transition-colors"
                                onClick={() => toggleModule(module.id)}
                              >
                                <div className="flex items-center justify-between">
                                  <div className="flex items-center gap-3">
                                    <Badge variant="outline" className="h-6 w-6 p-0 flex items-center justify-center">
                                      {module.ordre}
                                    </Badge>
                                    <div>
                                      <p className="font-medium text-sm">{module.titre}</p>
                                      <p className="text-xs text-muted-foreground">
                                        {module.lecons?.length || 0} leçons
                                      </p>
                                    </div>
                                  </div>
                                  {expandedModules.includes(module.id) ? (
                                    <ChevronDown className="h-4 w-4 text-muted-foreground" />
                                  ) : (
                                    <ChevronRight className="h-4 w-4 text-muted-foreground" />
                                  )}
                                </div>
                              </CardHeader>
                            </CollapsibleTrigger>
                            <CollapsibleContent>
                              <Separator />
                              <CardContent className="py-3">
                                {module.lecons && module.lecons.length > 0 ? (
                                  <div className="space-y-2">
                                    {module.lecons.map((lecon) => (
                                      <div key={lecon.id} className="flex items-center gap-3 p-2 rounded hover:bg-muted/30">
                                        <FileText className="h-4 w-4 text-muted-foreground" />
                                        <div className="flex-1">
                                          <p className="text-sm">{lecon.titre}</p>
                                          <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                            <Badge variant="outline" className="text-xs py-0">{lecon.type}</Badge>
                                            <span>{lecon.duree_estimee_minutes} min</span>
                                          </div>
                                        </div>
                                      </div>
                                    ))}
                                  </div>
                                ) : (
                                  <p className="text-sm text-muted-foreground text-center py-2">
                                    Aucune leçon dans ce module
                                  </p>
                                )}
                              </CardContent>
                            </CollapsibleContent>
                          </Card>
                        </Collapsible>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            </ScrollArea>
            <DialogFooter>
              <Button variant="outline" onClick={() => setShowViewDialog(false)}>Fermer</Button>
              <Button onClick={() => { setShowViewDialog(false); router.push(`/admin/parcours/${selectedParcours?.id}/modifier`); }}>
                <Edit className="h-4 w-4 mr-2" />
                Modifier
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>
    </TooltipProvider>
  );
}
