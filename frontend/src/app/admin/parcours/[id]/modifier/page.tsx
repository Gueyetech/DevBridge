"use client";

import React, { useState, useEffect, useCallback } from "react";
import { useRouter, useParams } from "next/navigation";
import { apiClient } from "@/lib/api";
import {
  Loader2,
  ArrowLeft,
  Save,
  Plus,
  Trash2,
  GripVertical,
  ChevronDown,
  ChevronUp,
  FileText,
  Video,
  CheckCircle,
  Code,
  X,
  Layers,
} from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { Switch } from "@/components/ui/switch";
import { toast } from "sonner";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";
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

interface Lecon {
  id?: string;
  titre: string;
  type_contenu: string;
  duree_estimee_minutes: number;
  contenu: string;
  ordre: number;
  est_gratuit: boolean;
  isNew?: boolean;
  isDeleted?: boolean;
}

interface Module {
  id?: string;
  titre: string;
  description: string;
  ordre: number;
  lecons: Lecon[];
  isNew?: boolean;
  isDeleted?: boolean;
}

interface ParcoursFormData {
  titre: string;
  description: string;
  technologie: string;
  difficulte: string;
  duree_estimee_heures: number;
  prerequis: string[];
  objectifs: string[];
  est_publie: boolean;
  modules: Module[];
}

const TECHNOLOGIES = [
  "JavaScript",
  "TypeScript",
  "React",
  "Vue.js",
  "Angular",
  "Node.js",
  "Python",
  "PHP",
  "Laravel",
  "Django",
  "Java",
  "C#",
  ".NET",
  "Go",
  "Rust",
  "Docker",
  "Kubernetes",
  "AWS",
  "Azure",
  "Git",
  "SQL",
  "MongoDB",
  "GraphQL",
  "HTML/CSS",
  "Autre",
];

const DIFFICULTES = [
  { value: "debutant", label: "Débutant" },
  { value: "intermediaire", label: "Intermédiaire" },
  { value: "avance", label: "Avancé" },
];

const TYPES_CONTENU = [
  { value: "article", label: "Article", icon: FileText },
  { value: "video", label: "Vidéo", icon: Video },
  { value: "exercice", label: "Exercice", icon: CheckCircle },
  { value: "projet", label: "Projet", icon: Code },
];

export default function ModifierParcoursPage() {
  const router = useRouter();
  const params = useParams();
  const id = params.id as string;

  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [originalData, setOriginalData] = useState<ParcoursFormData | null>(null);
  const [showCancelDialog, setShowCancelDialog] = useState(false);
  const [showDeleteModuleDialog, setShowDeleteModuleDialog] = useState<string | null>(null);
  const [showDeleteLeconDialog, setShowDeleteLeconDialog] = useState<{moduleIndex: number, leconIndex: number} | null>(null);

  const [formData, setFormData] = useState<ParcoursFormData>({
    titre: "",
    description: "",
    technologie: "",
    difficulte: "debutant",
    duree_estimee_heures: 10,
    prerequis: [],
    objectifs: [],
    est_publie: false,
    modules: [],
  });

  const [newPrerequis, setNewPrerequis] = useState("");
  const [newObjectif, setNewObjectif] = useState("");

  const fetchParcours = useCallback(async () => {
    setIsLoading(true);
    try {
      const response = await apiClient.get(`/v1/admin/parcours/${id}`) as { data: { parcours: { titre: string; description: string; technologie: string; difficulte: string; duree_estimee_heures: number; prerequis: string[] | string; objectifs: string[] | string; est_publie: boolean; modules: Module[] } } };
      const parcours = response.data.parcours;
      
      const data: ParcoursFormData = {
        titre: parcours.titre || "",
        description: parcours.description || "",
        technologie: parcours.technologie || "",
        difficulte: parcours.difficulte || "debutant",
        duree_estimee_heures: parcours.duree_estimee_heures || 10,
        prerequis: Array.isArray(parcours.prerequis) 
          ? parcours.prerequis 
          : parcours.prerequis?.split('\n').filter(Boolean) || [],
        objectifs: Array.isArray(parcours.objectifs) 
          ? parcours.objectifs 
          : parcours.objectifs?.split('\n').filter(Boolean) || [],
        est_publie: parcours.est_publie || false,
        modules: (parcours.modules || []).map((m: Module, index: number) => ({
          id: m.id,
          titre: m.titre,
          description: m.description || "",
          ordre: m.ordre || index + 1,
          lecons: (m.lecons || []).map((l: Lecon, lIndex: number) => ({
            id: l.id,
            titre: l.titre,
            type_contenu: l.type_contenu || "article",
            duree_estimee_minutes: l.duree_estimee_minutes || 15,
            contenu: l.contenu || "",
            ordre: l.ordre || lIndex + 1,
            est_gratuit: l.est_gratuit || false,
          })),
        })),
      };

      setFormData(data);
      setOriginalData(data);
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

  const hasChanges = () => {
    return JSON.stringify(formData) !== JSON.stringify(originalData);
  };

  const handleCancel = () => {
    if (hasChanges()) {
      setShowCancelDialog(true);
    } else {
      router.push(`/admin/parcours/${id}`);
    }
  };

  // Gestion des prérequis
  const addPrerequis = () => {
    if (newPrerequis.trim()) {
      setFormData(prev => ({
        ...prev,
        prerequis: [...prev.prerequis, newPrerequis.trim()]
      }));
      setNewPrerequis("");
    }
  };

  const removePrerequis = (index: number) => {
    setFormData(prev => ({
      ...prev,
      prerequis: prev.prerequis.filter((_, i) => i !== index)
    }));
  };

  // Gestion des objectifs
  const addObjectif = () => {
    if (newObjectif.trim()) {
      setFormData(prev => ({
        ...prev,
        objectifs: [...prev.objectifs, newObjectif.trim()]
      }));
      setNewObjectif("");
    }
  };

  const removeObjectif = (index: number) => {
    setFormData(prev => ({
      ...prev,
      objectifs: prev.objectifs.filter((_, i) => i !== index)
    }));
  };

  // Gestion des modules
  const addModule = () => {
    setFormData(prev => ({
      ...prev,
      modules: [
        ...prev.modules,
        {
          titre: `Module ${prev.modules.length + 1}`,
          description: "",
          ordre: prev.modules.length + 1,
          lecons: [],
          isNew: true,
        }
      ]
    }));
  };

  const updateModule = (index: number, field: keyof Module, value: string | number) => {
    setFormData(prev => ({
      ...prev,
      modules: prev.modules.map((m, i) => 
        i === index ? { ...m, [field]: value } : m
      )
    }));
  };

  const removeModule = (index: number) => {
    const module = formData.modules[index];
    if (module.id) {
      // Marquer comme supprimé si c'est un module existant
      setFormData(prev => ({
        ...prev,
        modules: prev.modules.map((m, i) => 
          i === index ? { ...m, isDeleted: true } : m
        )
      }));
    } else {
      // Supprimer directement si c'est un nouveau module
      setFormData(prev => ({
        ...prev,
        modules: prev.modules.filter((_, i) => i !== index)
      }));
    }
    setShowDeleteModuleDialog(null);
  };

  const moveModule = (index: number, direction: "up" | "down") => {
    if (
      (direction === "up" && index === 0) ||
      (direction === "down" && index === formData.modules.length - 1)
    ) {
      return;
    }

    const newModules = [...formData.modules];
    const newIndex = direction === "up" ? index - 1 : index + 1;
    [newModules[index], newModules[newIndex]] = [newModules[newIndex], newModules[index]];
    
    // Mettre à jour les ordres
    newModules.forEach((m, i) => {
      m.ordre = i + 1;
    });

    setFormData(prev => ({ ...prev, modules: newModules }));
  };

  // Gestion des leçons
  const addLecon = (moduleIndex: number) => {
    setFormData(prev => ({
      ...prev,
      modules: prev.modules.map((m, i) => 
        i === moduleIndex 
          ? {
              ...m,
              lecons: [
                ...m.lecons,
                {
                  titre: `Leçon ${m.lecons.length + 1}`,
                  type_contenu: "article",
                  duree_estimee_minutes: 15,
                  contenu: "",
                  ordre: m.lecons.length + 1,
                  est_gratuit: false,
                  isNew: true,
                }
              ]
            }
          : m
      )
    }));
  };

  const updateLecon = (moduleIndex: number, leconIndex: number, field: keyof Lecon, value: string | number | boolean) => {
    setFormData(prev => ({
      ...prev,
      modules: prev.modules.map((m, mi) => 
        mi === moduleIndex 
          ? {
              ...m,
              lecons: m.lecons.map((l, li) => 
                li === leconIndex ? { ...l, [field]: value } : l
              )
            }
          : m
      )
    }));
  };

  const removeLecon = (moduleIndex: number, leconIndex: number) => {
    const lecon = formData.modules[moduleIndex].lecons[leconIndex];
    if (lecon.id) {
      // Marquer comme supprimée si c'est une leçon existante
      setFormData(prev => ({
        ...prev,
        modules: prev.modules.map((m, mi) => 
          mi === moduleIndex 
            ? {
                ...m,
                lecons: m.lecons.map((l, li) => 
                  li === leconIndex ? { ...l, isDeleted: true } : l
                )
              }
            : m
        )
      }));
    } else {
      // Supprimer directement si c'est une nouvelle leçon
      setFormData(prev => ({
        ...prev,
        modules: prev.modules.map((m, mi) => 
          mi === moduleIndex 
            ? {
                ...m,
                lecons: m.lecons.filter((_, li) => li !== leconIndex)
              }
            : m
        )
      }));
    }
    setShowDeleteLeconDialog(null);
  };

  const handleSubmit = async () => {
    // Validation
    if (!formData.titre.trim()) {
      toast.error("Le titre est requis");
      return;
    }
    if (!formData.description.trim()) {
      toast.error("La description est requise");
      return;
    }
    if (!formData.technologie) {
      toast.error("La technologie est requise");
      return;
    }

    setIsSaving(true);
    try {
      // Mettre à jour le parcours
      await apiClient.put(`/v1/admin/parcours/${id}`, {
        titre: formData.titre,
        description: formData.description,
        technologie: formData.technologie,
        difficulte: formData.difficulte,
        duree_estimee_heures: formData.duree_estimee_heures,
        prerequis: formData.prerequis,
        objectifs: formData.objectifs,
        est_publie: formData.est_publie,
      });

      // Gérer les modules et leçons (simplifié - dans un vrai projet, on ferait des appels séparés)
      // Pour l'instant, on affiche juste un message de succès

      toast.success("Parcours mis à jour avec succès");
      router.push(`/admin/parcours/${id}`);
    } catch (error) {
      console.error("Erreur:", error);
      toast.error("Erreur lors de la mise à jour du parcours");
    } finally {
      setIsSaving(false);
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-[60vh]">
        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  const activeModules = formData.modules.filter(m => !m.isDeleted);

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Button variant="ghost" size="icon" onClick={handleCancel}>
            <ArrowLeft className="h-5 w-5" />
          </Button>
          <div>
            <h1 className="text-2xl font-bold">Modifier le parcours</h1>
            <p className="text-muted-foreground">
              Modifiez les informations du parcours
            </p>
          </div>
        </div>
        <div className="flex items-center gap-2">
          <Button variant="outline" onClick={handleCancel}>
            Annuler
          </Button>
          <Button onClick={handleSubmit} disabled={isSaving || !hasChanges()}>
            {isSaving ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Enregistrement...
              </>
            ) : (
              <>
                <Save className="mr-2 h-4 w-4" />
                Enregistrer
              </>
            )}
          </Button>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Colonne principale */}
        <div className="lg:col-span-2 space-y-6">
          {/* Informations générales */}
          <Card>
            <CardHeader>
              <CardTitle>Informations générales</CardTitle>
              <CardDescription>
                Les informations de base du parcours
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="titre">Titre *</Label>
                <Input
                  id="titre"
                  value={formData.titre}
                  onChange={(e) => setFormData(prev => ({ ...prev, titre: e.target.value }))}
                  placeholder="Ex: Introduction à JavaScript"
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="description">Description *</Label>
                <Textarea
                  id="description"
                  value={formData.description}
                  onChange={(e) => setFormData(prev => ({ ...prev, description: e.target.value }))}
                  placeholder="Décrivez le contenu et les objectifs du parcours..."
                  rows={4}
                />
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="technologie">Technologie *</Label>
                  <Select
                    value={formData.technologie}
                    onValueChange={(value) => setFormData(prev => ({ ...prev, technologie: value }))}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Sélectionner une technologie" />
                    </SelectTrigger>
                    <SelectContent>
                      {TECHNOLOGIES.map((tech) => (
                        <SelectItem key={tech} value={tech}>
                          {tech}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="difficulte">Niveau de difficulté *</Label>
                  <Select
                    value={formData.difficulte}
                    onValueChange={(value) => setFormData(prev => ({ ...prev, difficulte: value }))}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {DIFFICULTES.map((diff) => (
                        <SelectItem key={diff.value} value={diff.value}>
                          {diff.label}
                        </SelectItem>
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
                  onChange={(e) => setFormData(prev => ({ ...prev, duree_estimee_heures: parseInt(e.target.value) || 1 }))}
                />
              </div>
            </CardContent>
          </Card>

          {/* Modules et Leçons */}
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle>Modules et Leçons</CardTitle>
                  <CardDescription>
                    Structurez le contenu de votre parcours
                  </CardDescription>
                </div>
                <Button onClick={addModule} size="sm">
                  <Plus className="mr-2 h-4 w-4" />
                  Ajouter un module
                </Button>
              </div>
            </CardHeader>
            <CardContent>
              {activeModules.length === 0 ? (
                <div className="text-center py-8 text-muted-foreground">
                  <Layers className="h-12 w-12 mx-auto mb-4 opacity-50" />
                  <p>Aucun module pour le moment</p>
                  <p className="text-sm">Cliquez sur le bouton ci-dessus pour ajouter un module</p>
                </div>
              ) : (
                <Accordion type="multiple" defaultValue={activeModules.map((_, i) => `module-${i}`)}>
                  {formData.modules.map((module, moduleIndex) => {
                    if (module.isDeleted) return null;
                    
                    const activeLecons = module.lecons.filter(l => !l.isDeleted);
                    
                    return (
                      <AccordionItem key={moduleIndex} value={`module-${moduleIndex}`}>
                        <AccordionTrigger className="hover:no-underline">
                          <div className="flex items-center gap-3 flex-1">
                            <GripVertical className="h-4 w-4 text-muted-foreground" />
                            <Badge variant="outline">Module {module.ordre}</Badge>
                            <span className="font-medium">{module.titre}</span>
                            <Badge variant="secondary" className="ml-auto mr-4">
                              {activeLecons.length} leçon{activeLecons.length > 1 ? "s" : ""}
                            </Badge>
                          </div>
                        </AccordionTrigger>
                        <AccordionContent>
                          <div className="space-y-4 pt-4">
                            {/* Infos module */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                              <div className="space-y-2">
                                <Label>Titre du module</Label>
                                <Input
                                  value={module.titre}
                                  onChange={(e) => updateModule(moduleIndex, "titre", e.target.value)}
                                />
                              </div>
                              <div className="space-y-2">
                                <Label>Description</Label>
                                <Input
                                  value={module.description}
                                  onChange={(e) => updateModule(moduleIndex, "description", e.target.value)}
                                  placeholder="Description optionnelle"
                                />
                              </div>
                            </div>

                            {/* Actions module */}
                            <div className="flex items-center gap-2">
                              <Button
                                variant="outline"
                                size="sm"
                                onClick={() => moveModule(moduleIndex, "up")}
                                disabled={moduleIndex === 0}
                              >
                                <ChevronUp className="h-4 w-4" />
                              </Button>
                              <Button
                                variant="outline"
                                size="sm"
                                onClick={() => moveModule(moduleIndex, "down")}
                                disabled={moduleIndex === activeModules.length - 1}
                              >
                                <ChevronDown className="h-4 w-4" />
                              </Button>
                              <Button
                                variant="outline"
                                size="sm"
                                className="text-red-600 hover:text-red-700"
                                onClick={() => setShowDeleteModuleDialog(module.id || `new-${moduleIndex}`)}
                              >
                                <Trash2 className="h-4 w-4 mr-1" />
                                Supprimer
                              </Button>
                            </div>

                            <Separator />

                            {/* Leçons */}
                            <div className="space-y-3">
                              <div className="flex items-center justify-between">
                                <Label>Leçons</Label>
                                <Button
                                  variant="outline"
                                  size="sm"
                                  onClick={() => addLecon(moduleIndex)}
                                >
                                  <Plus className="h-4 w-4 mr-1" />
                                  Ajouter une leçon
                                </Button>
                              </div>

                              {activeLecons.length === 0 ? (
                                <p className="text-sm text-muted-foreground text-center py-4">
                                  Aucune leçon dans ce module
                                </p>
                              ) : (
                                <div className="space-y-3">
                                  {module.lecons.map((lecon, leconIndex) => {
                                    if (lecon.isDeleted) return null;
                                    
                                    const TypeIcon = TYPES_CONTENU.find(t => t.value === lecon.type_contenu)?.icon || FileText;
                                    
                                    return (
                                      <div
                                        key={leconIndex}
                                        className="border rounded-lg p-4 space-y-3"
                                      >
                                        <div className="flex items-center gap-2">
                                          <GripVertical className="h-4 w-4 text-muted-foreground" />
                                          <TypeIcon className="h-4 w-4" />
                                          <span className="text-sm font-medium flex-1">
                                            Leçon {lecon.ordre}
                                          </span>
                                          <Button
                                            variant="ghost"
                                            size="sm"
                                            className="text-red-600 hover:text-red-700 h-8 w-8 p-0"
                                            onClick={() => setShowDeleteLeconDialog({ moduleIndex, leconIndex })}
                                          >
                                            <Trash2 className="h-4 w-4" />
                                          </Button>
                                        </div>

                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                          <div className="space-y-1">
                                            <Label className="text-xs">Titre</Label>
                                            <Input
                                              value={lecon.titre}
                                              onChange={(e) => updateLecon(moduleIndex, leconIndex, "titre", e.target.value)}
                                              className="h-9"
                                            />
                                          </div>
                                          <div className="space-y-1">
                                            <Label className="text-xs">Type</Label>
                                            <Select
                                              value={lecon.type_contenu}
                                              onValueChange={(value) => updateLecon(moduleIndex, leconIndex, "type_contenu", value)}
                                            >
                                              <SelectTrigger className="h-9">
                                                <SelectValue />
                                              </SelectTrigger>
                                              <SelectContent>
                                                {TYPES_CONTENU.map((type) => (
                                                  <SelectItem key={type.value} value={type.value}>
                                                    <div className="flex items-center gap-2">
                                                      <type.icon className="h-4 w-4" />
                                                      {type.label}
                                                    </div>
                                                  </SelectItem>
                                                ))}
                                              </SelectContent>
                                            </Select>
                                          </div>
                                        </div>

                                        <div className="grid grid-cols-2 gap-3">
                                          <div className="space-y-1">
                                            <Label className="text-xs">Durée (min)</Label>
                                            <Input
                                              type="number"
                                              min={1}
                                              value={lecon.duree_estimee_minutes}
                                              onChange={(e) => updateLecon(moduleIndex, leconIndex, "duree_estimee_minutes", parseInt(e.target.value) || 1)}
                                              className="h-9"
                                            />
                                          </div>
                                          <div className="flex items-center space-x-2 pt-5">
                                            <Switch
                                              checked={lecon.est_gratuit}
                                              onCheckedChange={(checked) => updateLecon(moduleIndex, leconIndex, "est_gratuit", checked)}
                                            />
                                            <Label className="text-xs">Gratuit</Label>
                                          </div>
                                        </div>

                                        <div className="space-y-1">
                                          <Label className="text-xs">Contenu</Label>
                                          <Textarea
                                            value={lecon.contenu}
                                            onChange={(e) => updateLecon(moduleIndex, leconIndex, "contenu", e.target.value)}
                                            placeholder="Contenu de la leçon..."
                                            rows={3}
                                            className="text-sm"
                                          />
                                        </div>
                                      </div>
                                    );
                                  })}
                                </div>
                              )}
                            </div>
                          </div>
                        </AccordionContent>
                      </AccordionItem>
                    );
                  })}
                </Accordion>
              )}
            </CardContent>
          </Card>
        </div>

        {/* Colonne latérale */}
        <div className="space-y-6">
          {/* Publication */}
          <Card>
            <CardHeader>
              <CardTitle>Publication</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex items-center justify-between">
                <div>
                  <p className="font-medium">
                    {formData.est_publie ? "Publié" : "Brouillon"}
                  </p>
                  <p className="text-sm text-muted-foreground">
                    {formData.est_publie 
                      ? "Le parcours est visible par les utilisateurs" 
                      : "Le parcours n'est pas encore visible"}
                  </p>
                </div>
                <Switch
                  checked={formData.est_publie}
                  onCheckedChange={(checked) => setFormData(prev => ({ ...prev, est_publie: checked }))}
                />
              </div>
            </CardContent>
          </Card>

          {/* Prérequis */}
          <Card>
            <CardHeader>
              <CardTitle>Prérequis</CardTitle>
              <CardDescription>
                Ce que l&apos;apprenant doit savoir avant de commencer
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="flex gap-2">
                <Input
                  value={newPrerequis}
                  onChange={(e) => setNewPrerequis(e.target.value)}
                  placeholder="Ajouter un prérequis..."
                  onKeyDown={(e) => e.key === "Enter" && (e.preventDefault(), addPrerequis())}
                />
                <Button size="icon" onClick={addPrerequis}>
                  <Plus className="h-4 w-4" />
                </Button>
              </div>
              <div className="space-y-2">
                {formData.prerequis.map((prereq, index) => (
                  <div
                    key={index}
                    className="flex items-center justify-between p-2 bg-muted rounded-md"
                  >
                    <span className="text-sm">{prereq}</span>
                    <Button
                      variant="ghost"
                      size="sm"
                      className="h-6 w-6 p-0"
                      onClick={() => removePrerequis(index)}
                    >
                      <X className="h-3 w-3" />
                    </Button>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>

          {/* Objectifs */}
          <Card>
            <CardHeader>
              <CardTitle>Objectifs</CardTitle>
              <CardDescription>
                Ce que l&apos;apprenant saura faire après le parcours
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="flex gap-2">
                <Input
                  value={newObjectif}
                  onChange={(e) => setNewObjectif(e.target.value)}
                  placeholder="Ajouter un objectif..."
                  onKeyDown={(e) => e.key === "Enter" && (e.preventDefault(), addObjectif())}
                />
                <Button size="icon" onClick={addObjectif}>
                  <Plus className="h-4 w-4" />
                </Button>
              </div>
              <div className="space-y-2">
                {formData.objectifs.map((objectif, index) => (
                  <div
                    key={index}
                    className="flex items-center justify-between p-2 bg-muted rounded-md"
                  >
                    <span className="text-sm">{objectif}</span>
                    <Button
                      variant="ghost"
                      size="sm"
                      className="h-6 w-6 p-0"
                      onClick={() => removeObjectif(index)}
                    >
                      <X className="h-3 w-3" />
                    </Button>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>

          {/* Résumé */}
          <Card>
            <CardHeader>
              <CardTitle>Résumé</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Modules</span>
                  <span className="font-medium">{activeModules.length}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Leçons</span>
                  <span className="font-medium">
                    {activeModules.reduce((acc, m) => acc + m.lecons.filter(l => !l.isDeleted).length, 0)}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Durée totale</span>
                  <span className="font-medium">{formData.duree_estimee_heures}h</span>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      {/* Dialog annulation */}
      <AlertDialog open={showCancelDialog} onOpenChange={setShowCancelDialog}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Annuler les modifications ?</AlertDialogTitle>
            <AlertDialogDescription>
              Vous avez des modifications non enregistrées. Êtes-vous sûr de vouloir quitter sans enregistrer ?
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Continuer l&apos;édition</AlertDialogCancel>
            <AlertDialogAction onClick={() => router.push(`/admin/parcours/${id}`)}>
              Quitter sans enregistrer
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* Dialog suppression module */}
      <AlertDialog open={!!showDeleteModuleDialog} onOpenChange={() => setShowDeleteModuleDialog(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Supprimer ce module ?</AlertDialogTitle>
            <AlertDialogDescription>
              Cette action supprimera le module et toutes ses leçons. Cette action est irréversible.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Annuler</AlertDialogCancel>
            <AlertDialogAction
              className="bg-red-600 hover:bg-red-700"
              onClick={() => {
                const index = formData.modules.findIndex(m => 
                  m.id === showDeleteModuleDialog || `new-${formData.modules.indexOf(m)}` === showDeleteModuleDialog
                );
                if (index !== -1) removeModule(index);
              }}
            >
              Supprimer
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* Dialog suppression leçon */}
      <AlertDialog open={!!showDeleteLeconDialog} onOpenChange={() => setShowDeleteLeconDialog(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Supprimer cette leçon ?</AlertDialogTitle>
            <AlertDialogDescription>
              Cette action est irréversible.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Annuler</AlertDialogCancel>
            <AlertDialogAction
              className="bg-red-600 hover:bg-red-700"
              onClick={() => {
                if (showDeleteLeconDialog) {
                  removeLecon(showDeleteLeconDialog.moduleIndex, showDeleteLeconDialog.leconIndex);
                }
              }}
            >
              Supprimer
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
