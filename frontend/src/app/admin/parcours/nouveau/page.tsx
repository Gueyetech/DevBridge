"use client";

import React, { useState } from "react";
import { useRouter } from "next/navigation";
import { apiClient } from "@/lib/api";
import {
  Loader2,
  ArrowLeft,
  Save,
  Plus,
  Trash2,
  GripVertical,
  BookOpen,
  FileText,
  Video,
  Code,
  CheckCircle,
} from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
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
import { toast } from "sonner";
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from "@/components/ui/accordion";

interface Lecon {
  id: string;
  titre: string;
  type_contenu: "article" | "video" | "exercice" | "projet";
  duree_estimee_minutes: number;
  contenu: string;
  ordre: number;
}

interface Module {
  id: string;
  titre: string;
  description: string;
  ordre: number;
  lecons: Lecon[];
}

const DIFFICULTES = [
  { value: "debutant", label: "Débutant", color: "bg-green-500" },
  { value: "intermediaire", label: "Intermédiaire", color: "bg-yellow-500" },
  { value: "avance", label: "Avancé", color: "bg-orange-500" },
];

const TECHNOLOGIES = [
  "JavaScript", "TypeScript", "Python", "PHP", "Java", "C#", "Go",
  "React", "Vue.js", "Angular", "Node.js", "Laravel", "Django", "Spring",
  "Docker", "Git", "AWS", "Azure", "Kubernetes"
];

const TYPES_LECON = [
  { value: "article", label: "Article", icon: FileText },
  { value: "video", label: "Vidéo", icon: Video },
  { value: "exercice", label: "Exercice", icon: CheckCircle },
  { value: "projet", label: "Projet", icon: Code },
];

const generateId = () => Math.random().toString(36).substring(2, 11);

export default function NouveauParcoursPage() {
  const router = useRouter();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [activeStep, setActiveStep] = useState(1);

  // Données du parcours
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

  // Modules et leçons
  const [modules, setModules] = useState<Module[]>([]);

  // Ajouter un module
  const addModule = () => {
    const newModule: Module = {
      id: generateId(),
      titre: `Module ${modules.length + 1}`,
      description: "",
      ordre: modules.length + 1,
      lecons: [],
    };
    setModules([...modules, newModule]);
  };

  // Supprimer un module
  const removeModule = (moduleId: string) => {
    setModules(modules.filter((m) => m.id !== moduleId));
  };

  // Mettre à jour un module
  const updateModule = (moduleId: string, field: string, value: string) => {
    setModules(
      modules.map((m) =>
        m.id === moduleId ? { ...m, [field]: value } : m
      )
    );
  };

  // Ajouter une leçon à un module
  const addLecon = (moduleId: string) => {
    const module = modules.find((m) => m.id === moduleId);
    if (!module) return;

    const newLecon: Lecon = {
      id: generateId(),
      titre: `Leçon ${module.lecons.length + 1}`,
      type_contenu: "article",
      duree_estimee_minutes: 15,
      contenu: "",
      ordre: module.lecons.length + 1,
    };

    setModules(
      modules.map((m) =>
        m.id === moduleId ? { ...m, lecons: [...m.lecons, newLecon] } : m
      )
    );
  };

  // Supprimer une leçon
  const removeLecon = (moduleId: string, leconId: string) => {
    setModules(
      modules.map((m) =>
        m.id === moduleId
          ? { ...m, lecons: m.lecons.filter((l) => l.id !== leconId) }
          : m
      )
    );
  };

  // Mettre à jour une leçon
  const updateLecon = (
    moduleId: string,
    leconId: string,
    field: string,
    value: string | number
  ) => {
    setModules(
      modules.map((m) =>
        m.id === moduleId
          ? {
              ...m,
              lecons: m.lecons.map((l) =>
                l.id === leconId ? { ...l, [field]: value } : l
              ),
            }
          : m
      )
    );
  };

  // Calculer la durée totale
  const calculerDureeTotale = () => {
    return modules.reduce((total, module) => {
      return (
        total +
        module.lecons.reduce((sum, lecon) => sum + lecon.duree_estimee_minutes, 0)
      );
    }, 0);
  };

  // Soumettre le formulaire
  const handleSubmit = async () => {
    // Validation
    if (!formData.titre.trim()) {
      toast.error("Le titre est obligatoire");
      setActiveStep(1);
      return;
    }
    if (!formData.description.trim()) {
      toast.error("La description est obligatoire");
      setActiveStep(1);
      return;
    }
    if (!formData.technologie) {
      toast.error("La technologie est obligatoire");
      setActiveStep(1);
      return;
    }

    setIsSubmitting(true);

    try {
      const payload = {
        ...formData,
        prerequis: formData.prerequis.split("\n").filter((p) => p.trim()),
        objectifs: formData.objectifs.split("\n").filter((o) => o.trim()),
        modules: modules.map((m, idx) => ({
          titre: m.titre,
          description: m.description,
          ordre: idx + 1,
          lecons: m.lecons.map((l, lidx) => ({
            titre: l.titre,
            type_contenu: l.type_contenu,
            duree_estimee_minutes: l.duree_estimee_minutes,
            contenu: l.contenu,
            ordre: lidx + 1,
          })),
        })),
      };

      await apiClient.post("/v1/admin/parcours", payload);
      toast.success("Parcours créé avec succès !");
      router.push("/admin/parcours");
    } catch (error: unknown) {
      console.error("Erreur:", error);
      const errorMessage = error instanceof Error ? error.message : "Erreur lors de la création";
      toast.error(errorMessage);
    } finally {
      setIsSubmitting(false);
    }
  };

  const steps = [
    { id: 1, title: "Informations", description: "Détails du parcours" },
    { id: 2, title: "Contenu", description: "Modules et leçons" },
    { id: 3, title: "Aperçu", description: "Vérification finale" },
  ];

  return (
    <div className="container mx-auto py-6 space-y-6">
      {/* En-tête */}
      <div className="flex items-center gap-4">
        <Button
          variant="ghost"
          size="icon"
          onClick={() => router.push("/admin/parcours")}
        >
          <ArrowLeft className="h-5 w-5" />
        </Button>
        <div>
          <h1 className="text-2xl font-bold">Nouveau Parcours</h1>
          <p className="text-muted-foreground">
            Créez un nouveau parcours d&apos;apprentissage
          </p>
        </div>
      </div>

      {/* Stepper */}
      <div className="flex items-center justify-center gap-4">
        {steps.map((step, index) => (
          <React.Fragment key={step.id}>
            <button
              onClick={() => setActiveStep(step.id)}
              className={`flex items-center gap-2 p-3 rounded-lg transition-colors ${
                activeStep === step.id
                  ? "bg-primary text-primary-foreground"
                  : activeStep > step.id
                  ? "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400"
                  : "bg-muted text-muted-foreground"
              }`}
            >
              <div
                className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium ${
                  activeStep === step.id
                    ? "bg-primary-foreground text-primary"
                    : activeStep > step.id
                    ? "bg-green-500 text-white"
                    : "bg-muted-foreground/20"
                }`}
              >
                {activeStep > step.id ? "✓" : step.id}
              </div>
              <div className="hidden sm:block text-left">
                <div className="font-medium">{step.title}</div>
                <div className="text-xs opacity-70">{step.description}</div>
              </div>
            </button>
            {index < steps.length - 1 && (
              <div className="hidden sm:block w-16 h-0.5 bg-muted" />
            )}
          </React.Fragment>
        ))}
      </div>

      {/* Étape 1: Informations */}
      {activeStep === 1 && (
        <Card>
          <CardHeader>
            <CardTitle>Informations générales</CardTitle>
            <CardDescription>
              Définissez les informations de base du parcours
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="space-y-2">
                <Label htmlFor="titre">Titre *</Label>
                <Input
                  id="titre"
                  placeholder="Ex: Introduction à React"
                  value={formData.titre}
                  onChange={(e) =>
                    setFormData({ ...formData, titre: e.target.value })
                  }
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="technologie">Technologie *</Label>
                <Select
                  value={formData.technologie}
                  onValueChange={(value) =>
                    setFormData({ ...formData, technologie: value })
                  }
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
                  onValueChange={(value) =>
                    setFormData({ ...formData, difficulte: value })
                  }
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {DIFFICULTES.map((diff) => (
                      <SelectItem key={diff.value} value={diff.value}>
                        <div className="flex items-center gap-2">
                          <div className={`w-2 h-2 rounded-full ${diff.color}`} />
                          {diff.label}
                        </div>
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="duree">Durée estimée (heures)</Label>
                <Input
                  id="duree"
                  type="number"
                  min={1}
                  value={formData.duree_estimee_heures}
                  onChange={(e) =>
                    setFormData({
                      ...formData,
                      duree_estimee_heures: parseInt(e.target.value) || 0,
                    })
                  }
                />
              </div>
            </div>

            <div className="space-y-2">
              <Label htmlFor="description">Description *</Label>
              <Textarea
                id="description"
                placeholder="Décrivez le parcours en détail..."
                rows={4}
                value={formData.description}
                onChange={(e) =>
                  setFormData({ ...formData, description: e.target.value })
                }
              />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="space-y-2">
                <Label htmlFor="prerequis">Prérequis (un par ligne)</Label>
                <Textarea
                  id="prerequis"
                  placeholder="Ex:&#10;Bases en HTML/CSS&#10;Notions de JavaScript"
                  rows={4}
                  value={formData.prerequis}
                  onChange={(e) =>
                    setFormData({ ...formData, prerequis: e.target.value })
                  }
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="objectifs">Objectifs (un par ligne)</Label>
                <Textarea
                  id="objectifs"
                  placeholder="Ex:&#10;Maîtriser les composants React&#10;Comprendre les hooks"
                  rows={4}
                  value={formData.objectifs}
                  onChange={(e) =>
                    setFormData({ ...formData, objectifs: e.target.value })
                  }
                />
              </div>
            </div>

            <div className="flex items-center justify-between p-4 bg-muted rounded-lg">
              <div>
                <Label htmlFor="publie">Publier immédiatement</Label>
                <p className="text-sm text-muted-foreground">
                  Le parcours sera visible par les utilisateurs
                </p>
              </div>
              <Switch
                id="publie"
                checked={formData.est_publie}
                onCheckedChange={(checked) =>
                  setFormData({ ...formData, est_publie: checked })
                }
              />
            </div>

            <div className="flex justify-end">
              <Button onClick={() => setActiveStep(2)}>
                Suivant : Contenu
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Étape 2: Contenu */}
      {activeStep === 2 && (
        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <div>
                <CardTitle>Modules et Leçons</CardTitle>
                <CardDescription>
                  Structurez le contenu de votre parcours
                </CardDescription>
              </div>
              <Button onClick={addModule}>
                <Plus className="h-4 w-4 mr-2" />
                Ajouter un module
              </Button>
            </div>
          </CardHeader>
          <CardContent className="space-y-4">
            {modules.length === 0 ? (
              <div className="text-center py-12 border-2 border-dashed rounded-lg">
                <BookOpen className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                <h3 className="text-lg font-medium">Aucun module</h3>
                <p className="text-muted-foreground mb-4">
                  Commencez par ajouter un module à votre parcours
                </p>
                <Button onClick={addModule}>
                  <Plus className="h-4 w-4 mr-2" />
                  Ajouter un module
                </Button>
              </div>
            ) : (
              <Accordion type="multiple" className="space-y-4">
                {modules.map((module, moduleIndex) => (
                  <AccordionItem
                    key={module.id}
                    value={module.id}
                    className="border rounded-lg px-4"
                  >
                    <AccordionTrigger className="hover:no-underline">
                      <div className="flex items-center gap-3">
                        <GripVertical className="h-4 w-4 text-muted-foreground" />
                        <Badge variant="outline">Module {moduleIndex + 1}</Badge>
                        <span className="font-medium">{module.titre}</span>
                        <Badge variant="secondary">
                          {module.lecons.length} leçon(s)
                        </Badge>
                      </div>
                    </AccordionTrigger>
                    <AccordionContent className="space-y-4 pt-4">
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                          <Label>Titre du module</Label>
                          <Input
                            value={module.titre}
                            onChange={(e) =>
                              updateModule(module.id, "titre", e.target.value)
                            }
                          />
                        </div>
                        <div className="space-y-2">
                          <Label>Description</Label>
                          <Input
                            value={module.description}
                            onChange={(e) =>
                              updateModule(module.id, "description", e.target.value)
                            }
                            placeholder="Description du module"
                          />
                        </div>
                      </div>

                      <Separator />

                      <div className="space-y-3">
                        <div className="flex items-center justify-between">
                          <Label>Leçons</Label>
                          <Button
                            size="sm"
                            variant="outline"
                            onClick={() => addLecon(module.id)}
                          >
                            <Plus className="h-3 w-3 mr-1" />
                            Ajouter une leçon
                          </Button>
                        </div>

                        {module.lecons.length === 0 ? (
                          <p className="text-sm text-muted-foreground text-center py-4">
                            Aucune leçon dans ce module
                          </p>
                        ) : (
                          <div className="space-y-3">
                            {module.lecons.map((lecon, leconIndex) => (
                              <div
                                key={lecon.id}
                                className="flex items-start gap-3 p-3 bg-muted rounded-lg"
                              >
                                <GripVertical className="h-4 w-4 text-muted-foreground mt-2" />
                                <div className="flex-1 grid grid-cols-1 md:grid-cols-4 gap-3">
                                  <div className="md:col-span-2 space-y-1">
                                    <Label className="text-xs">Titre</Label>
                                    <Input
                                      size={1}
                                      value={lecon.titre}
                                      onChange={(e) =>
                                        updateLecon(
                                          module.id,
                                          lecon.id,
                                          "titre",
                                          e.target.value
                                        )
                                      }
                                      placeholder={`Leçon ${leconIndex + 1}`}
                                    />
                                  </div>
                                  <div className="space-y-1">
                                    <Label className="text-xs">Type</Label>
                                    <Select
                                      value={lecon.type_contenu}
                                      onValueChange={(value) =>
                                        updateLecon(
                                          module.id,
                                          lecon.id,
                                          "type_contenu",
                                          value
                                        )
                                      }
                                    >
                                      <SelectTrigger>
                                        <SelectValue />
                                      </SelectTrigger>
                                      <SelectContent>
                                        {TYPES_LECON.map((type) => (
                                          <SelectItem
                                            key={type.value}
                                            value={type.value}
                                          >
                                            <div className="flex items-center gap-2">
                                              <type.icon className="h-3 w-3" />
                                              {type.label}
                                            </div>
                                          </SelectItem>
                                        ))}
                                      </SelectContent>
                                    </Select>
                                  </div>
                                  <div className="space-y-1">
                                    <Label className="text-xs">Durée (min)</Label>
                                    <Input
                                      type="number"
                                      min={1}
                                      value={lecon.duree_estimee_minutes}
                                      onChange={(e) =>
                                        updateLecon(
                                          module.id,
                                          lecon.id,
                                          "duree_estimee_minutes",
                                          parseInt(e.target.value) || 0
                                        )
                                      }
                                    />
                                  </div>
                                </div>
                                <Button
                                  size="icon"
                                  variant="ghost"
                                  className="text-destructive hover:text-destructive"
                                  onClick={() => removeLecon(module.id, lecon.id)}
                                >
                                  <Trash2 className="h-4 w-4" />
                                </Button>
                              </div>
                            ))}
                          </div>
                        )}
                      </div>

                      <Separator />

                      <div className="flex justify-end">
                        <Button
                          variant="destructive"
                          size="sm"
                          onClick={() => removeModule(module.id)}
                        >
                          <Trash2 className="h-4 w-4 mr-2" />
                          Supprimer le module
                        </Button>
                      </div>
                    </AccordionContent>
                  </AccordionItem>
                ))}
              </Accordion>
            )}

            <div className="flex justify-between pt-4">
              <Button variant="outline" onClick={() => setActiveStep(1)}>
                Retour
              </Button>
              <Button onClick={() => setActiveStep(3)}>
                Suivant : Aperçu
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Étape 3: Aperçu */}
      {activeStep === 3 && (
        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Aperçu du parcours</CardTitle>
              <CardDescription>
                Vérifiez les informations avant de créer le parcours
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              {/* Infos générales */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <h3 className="text-lg font-semibold mb-4">{formData.titre || "Sans titre"}</h3>
                  <p className="text-muted-foreground mb-4">
                    {formData.description || "Aucune description"}
                  </p>
                  <div className="flex flex-wrap gap-2">
                    <Badge>{formData.technologie || "Technologie non définie"}</Badge>
                    <Badge variant="outline">
                      {DIFFICULTES.find((d) => d.value === formData.difficulte)?.label}
                    </Badge>
                    <Badge variant={formData.est_publie ? "default" : "secondary"}>
                      {formData.est_publie ? "Sera publié" : "Brouillon"}
                    </Badge>
                  </div>
                </div>
                <div className="space-y-4">
                  <div className="p-4 bg-muted rounded-lg">
                    <div className="grid grid-cols-2 gap-4 text-center">
                      <div>
                        <div className="text-2xl font-bold">{modules.length}</div>
                        <div className="text-sm text-muted-foreground">Modules</div>
                      </div>
                      <div>
                        <div className="text-2xl font-bold">
                          {modules.reduce((sum, m) => sum + m.lecons.length, 0)}
                        </div>
                        <div className="text-sm text-muted-foreground">Leçons</div>
                      </div>
                      <div>
                        <div className="text-2xl font-bold">
                          {Math.round(calculerDureeTotale() / 60)}h
                        </div>
                        <div className="text-sm text-muted-foreground">Durée totale</div>
                      </div>
                      <div>
                        <div className="text-2xl font-bold">
                          {formData.duree_estimee_heures}h
                        </div>
                        <div className="text-sm text-muted-foreground">Durée estimée</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <Separator />

              {/* Prérequis & Objectifs */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <h4 className="font-medium mb-2">Prérequis</h4>
                  {formData.prerequis ? (
                    <ul className="list-disc list-inside text-sm text-muted-foreground space-y-1">
                      {formData.prerequis.split("\n").filter(p => p.trim()).map((p, i) => (
                        <li key={i}>{p}</li>
                      ))}
                    </ul>
                  ) : (
                    <p className="text-sm text-muted-foreground">Aucun prérequis défini</p>
                  )}
                </div>
                <div>
                  <h4 className="font-medium mb-2">Objectifs</h4>
                  {formData.objectifs ? (
                    <ul className="list-disc list-inside text-sm text-muted-foreground space-y-1">
                      {formData.objectifs.split("\n").filter(o => o.trim()).map((o, i) => (
                        <li key={i}>{o}</li>
                      ))}
                    </ul>
                  ) : (
                    <p className="text-sm text-muted-foreground">Aucun objectif défini</p>
                  )}
                </div>
              </div>

              <Separator />

              {/* Modules */}
              <div>
                <h4 className="font-medium mb-4">Structure du contenu</h4>
                {modules.length === 0 ? (
                  <p className="text-sm text-muted-foreground">Aucun module défini</p>
                ) : (
                  <div className="space-y-3">
                    {modules.map((module, index) => (
                      <div key={module.id} className="p-4 border rounded-lg">
                        <div className="flex items-center gap-3 mb-2">
                          <Badge variant="outline">Module {index + 1}</Badge>
                          <span className="font-medium">{module.titre}</span>
                        </div>
                        {module.lecons.length > 0 && (
                          <div className="ml-4 space-y-1">
                            {module.lecons.map((lecon, lIndex) => (
                              <div
                                key={lecon.id}
                                className="flex items-center gap-2 text-sm text-muted-foreground"
                              >
                                <span>{lIndex + 1}.</span>
                                {TYPES_LECON.find(t => t.value === lecon.type_contenu)?.icon && 
                                  React.createElement(
                                    TYPES_LECON.find(t => t.value === lecon.type_contenu)!.icon,
                                    { className: "h-3 w-3" }
                                  )
                                }
                                <span>{lecon.titre}</span>
                                <span className="text-xs">({lecon.duree_estimee_minutes} min)</span>
                              </div>
                            ))}
                          </div>
                        )}
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </CardContent>
          </Card>

          <div className="flex justify-between">
            <Button variant="outline" onClick={() => setActiveStep(2)}>
              Retour
            </Button>
            <Button onClick={handleSubmit} disabled={isSubmitting}>
              {isSubmitting ? (
                <>
                  <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                  Création en cours...
                </>
              ) : (
                <>
                  <Save className="h-4 w-4 mr-2" />
                  Créer le parcours
                </>
              )}
            </Button>
          </div>
        </div>
      )}
    </div>
  );
}
