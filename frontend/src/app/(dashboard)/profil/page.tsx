"use client";

import { useState, useEffect } from "react";
import { useAuthStore } from "@/stores/auth-store";
import { apiClient } from "@/lib/api";
import { Loader2, Camera, Trash2, Save, User, Mail, Lock, Globe, Github, Linkedin, MapPin } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";

interface ProfilData {
  utilisateur: {
    id: string;
    prenom: string;
    nom: string;
    nom_complet: string;
    email: string;
    role: string;
    avatar: string | null;
    niveau: number;
    points: number;
    est_actif: boolean;
    created_at: string;
  };
  profil: {
    bio: string | null;
    niveau: string | null;
    technologies: string[];
    github_url: string | null;
    linkedin_url: string | null;
    portfolio_url: string | null;
    ville: string | null;
    pays: string | null;
    est_disponible_mentorat: boolean;
  } | null;
}

interface StatsData {
  statistiques: {
    points: number;
    niveau: number;
    parcours_inscrits: number;
    parcours_termines: number;
    projets: number;
    badges: number;
    competences: number;
    etudiants_mentores?: number;
    sessions_mentorat?: number;
  };
}

export default function ProfilPage() {
  const { utilisateur, setUtilisateur } = useAuthStore();
  const [profilData, setProfilData] = useState<ProfilData | null>(null);
  const [stats, setStats] = useState<StatsData | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [message, setMessage] = useState<{ type: "success" | "error"; text: string } | null>(null);

  // Formulaires
  const [infosForm, setInfosForm] = useState({
    prenom: "",
    nom: "",
    email: "",
  });

  const [profilForm, setProfilForm] = useState({
    bio: "",
    niveau: "debutant",
    technologies: [] as string[],
    github_url: "",
    linkedin_url: "",
    portfolio_url: "",
    ville: "",
    pays: "",
    est_disponible_mentorat: false,
  });

  const [passwordForm, setPasswordForm] = useState({
    mot_de_passe_actuel: "",
    mot_de_passe: "",
    mot_de_passe_confirmation: "",
  });

  const [newTech, setNewTech] = useState("");

  useEffect(() => {
    fetchProfil();
    fetchStats();
  }, []);

  const fetchProfil = async () => {
    try {
      const response = await apiClient.get<ProfilData>("/v1/profil");
      setProfilData(response);
      
      setInfosForm({
        prenom: response.utilisateur.prenom,
        nom: response.utilisateur.nom,
        email: response.utilisateur.email,
      });

      if (response.profil) {
        setProfilForm({
          bio: response.profil.bio || "",
          niveau: response.profil.niveau || "debutant",
          technologies: response.profil.technologies || [],
          github_url: response.profil.github_url || "",
          linkedin_url: response.profil.linkedin_url || "",
          portfolio_url: response.profil.portfolio_url || "",
          ville: response.profil.ville || "",
          pays: response.profil.pays || "",
          est_disponible_mentorat: response.profil.est_disponible_mentorat || false,
        });
      }
    } catch (error) {
      console.error("Erreur lors du chargement du profil:", error);
    } finally {
      setIsLoading(false);
    }
  };

  const fetchStats = async () => {
    try {
      const response = await apiClient.get<StatsData>("/v1/profil/stats");
      setStats(response);
    } catch (error) {
      console.error("Erreur lors du chargement des stats:", error);
    }
  };

  const handleUpdateInfos = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSaving(true);
    setMessage(null);

    try {
      const response = await apiClient.put<{ message: string; utilisateur: ProfilData["utilisateur"] }>(
        "/v1/profil/infos",
        infosForm
      );
      
      setMessage({ type: "success", text: response.message });
      
      // Mettre à jour le store auth
      if (utilisateur) {
        setUtilisateur({
          ...utilisateur,
          prenom: response.utilisateur.prenom,
          nom: response.utilisateur.nom,
          email: response.utilisateur.email,
        });
      }
    } catch (error: unknown) {
      const err = error as { message?: string };
      setMessage({ type: "error", text: err.message || "Erreur lors de la mise à jour" });
    } finally {
      setIsSaving(false);
    }
  };

  const handleUpdateProfil = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSaving(true);
    setMessage(null);

    try {
      const response = await apiClient.put<{ message: string }>("/v1/profil/details", profilForm);
      setMessage({ type: "success", text: response.message });
    } catch (error: unknown) {
      const err = error as { message?: string };
      setMessage({ type: "error", text: err.message || "Erreur lors de la mise à jour" });
    } finally {
      setIsSaving(false);
    }
  };

  const handleUpdatePassword = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSaving(true);
    setMessage(null);

    try {
      const response = await apiClient.put<{ message: string }>("/v1/profil/mot-de-passe", passwordForm);
      setMessage({ type: "success", text: response.message });
      setPasswordForm({
        mot_de_passe_actuel: "",
        mot_de_passe: "",
        mot_de_passe_confirmation: "",
      });
    } catch (error: unknown) {
      const err = error as { message?: string };
      setMessage({ type: "error", text: err.message || "Erreur lors de la mise à jour du mot de passe" });
    } finally {
      setIsSaving(false);
    }
  };

  const handleAvatarUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append("avatar", file);

    setIsSaving(true);
    try {
      const response = await apiClient.upload<{ message: string; avatar: string }>("/v1/profil/avatar", formData);
      setMessage({ type: "success", text: response.message });
      
      // Mettre à jour le store auth
      if (utilisateur) {
        setUtilisateur({
          ...utilisateur,
          avatar: response.avatar,
        });
      }
      
      fetchProfil();
    } catch (error: unknown) {
      const err = error as { message?: string };
      setMessage({ type: "error", text: err.message || "Erreur lors du téléchargement de l'avatar" });
    } finally {
      setIsSaving(false);
    }
  };

  const handleDeleteAvatar = async () => {
    setIsSaving(true);
    try {
      const response = await apiClient.delete<{ message: string }>("/v1/profil/avatar");
      setMessage({ type: "success", text: response.message });
      
      if (utilisateur) {
        setUtilisateur({
          ...utilisateur,
          avatar: undefined,
        });
      }
      
      fetchProfil();
    } catch (error: unknown) {
      const err = error as { message?: string };
      setMessage({ type: "error", text: err.message || "Erreur lors de la suppression de l'avatar" });
    } finally {
      setIsSaving(false);
    }
  };

  const addTechnology = () => {
    if (newTech.trim() && !profilForm.technologies.includes(newTech.trim())) {
      setProfilForm({
        ...profilForm,
        technologies: [...profilForm.technologies, newTech.trim()],
      });
      setNewTech("");
    }
  };

  const removeTechnology = (tech: string) => {
    setProfilForm({
      ...profilForm,
      technologies: profilForm.technologies.filter((t) => t !== tech),
    });
  };

  const getInitials = (name: string) => {
    const parts = name.split(" ");
    if (parts.length >= 2) {
      return `${parts[0].charAt(0)}${parts[1].charAt(0)}`.toUpperCase();
    }
    return name.charAt(0).toUpperCase();
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
      <div>
        <h1 className="text-3xl font-bold">Mon Profil</h1>
        <p className="text-muted-foreground">Gérez vos informations personnelles et vos préférences</p>
      </div>

      {message && (
        <Alert variant={message.type === "error" ? "destructive" : "default"}>
          <AlertDescription>{message.text}</AlertDescription>
        </Alert>
      )}

      <div className="grid gap-6 md:grid-cols-3">
        {/* Carte Avatar et Stats */}
        <Card className="md:col-span-1">
          <CardHeader>
            <CardTitle>Photo de profil</CardTitle>
          </CardHeader>
          <CardContent className="flex flex-col items-center space-y-4">
            <div className="relative">
              <Avatar className="h-32 w-32">
                <AvatarImage src={profilData?.utilisateur.avatar || undefined} />
                <AvatarFallback className="text-2xl">
                  {getInitials(profilData?.utilisateur.nom_complet || "U")}
                </AvatarFallback>
              </Avatar>
              <label
                htmlFor="avatar-upload"
                className="absolute bottom-0 right-0 p-2 bg-primary text-primary-foreground rounded-full cursor-pointer hover:bg-primary/90"
              >
                <Camera className="h-4 w-4" />
                <input
                  id="avatar-upload"
                  type="file"
                  accept="image/*"
                  className="hidden"
                  onChange={handleAvatarUpload}
                />
              </label>
            </div>
            
            {profilData?.utilisateur.avatar && (
              <Button variant="outline" size="sm" onClick={handleDeleteAvatar}>
                <Trash2 className="h-4 w-4 mr-2" />
                Supprimer
              </Button>
            )}

            <Separator className="my-4" />

            {stats && (
              <div className="w-full space-y-3">
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Points</span>
                  <span className="font-semibold">{stats.statistiques.points}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Niveau</span>
                  <span className="font-semibold">{stats.statistiques.niveau}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Parcours</span>
                  <span className="font-semibold">
                    {stats.statistiques.parcours_termines}/{stats.statistiques.parcours_inscrits}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Projets</span>
                  <span className="font-semibold">{stats.statistiques.projets}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Badges</span>
                  <span className="font-semibold">{stats.statistiques.badges}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Compétences</span>
                  <span className="font-semibold">{stats.statistiques.competences}</span>
                </div>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Tabs pour les formulaires */}
        <Card className="md:col-span-2">
          <Tabs defaultValue="infos" className="w-full">
            <CardHeader>
              <TabsList className="grid w-full grid-cols-3">
                <TabsTrigger value="infos">Informations</TabsTrigger>
                <TabsTrigger value="profil">Profil</TabsTrigger>
                <TabsTrigger value="securite">Sécurité</TabsTrigger>
              </TabsList>
            </CardHeader>

            <CardContent>
              {/* Onglet Informations */}
              <TabsContent value="infos" className="space-y-4">
                <form onSubmit={handleUpdateInfos} className="space-y-4">
                  <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                      <Label htmlFor="prenom">
                        <User className="h-4 w-4 inline mr-2" />
                        Prénom
                      </Label>
                      <Input
                        id="prenom"
                        value={infosForm.prenom}
                        onChange={(e) => setInfosForm({ ...infosForm, prenom: e.target.value })}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="nom">Nom</Label>
                      <Input
                        id="nom"
                        value={infosForm.nom}
                        onChange={(e) => setInfosForm({ ...infosForm, nom: e.target.value })}
                      />
                    </div>
                  </div>
                  
                  <div className="space-y-2">
                    <Label htmlFor="email">
                      <Mail className="h-4 w-4 inline mr-2" />
                      Email
                    </Label>
                    <Input
                      id="email"
                      type="email"
                      value={infosForm.email}
                      onChange={(e) => setInfosForm({ ...infosForm, email: e.target.value })}
                    />
                  </div>

                  <Button type="submit" disabled={isSaving}>
                    {isSaving ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : <Save className="h-4 w-4 mr-2" />}
                    Enregistrer
                  </Button>
                </form>
              </TabsContent>

              {/* Onglet Profil */}
              <TabsContent value="profil" className="space-y-4">
                <form onSubmit={handleUpdateProfil} className="space-y-4">
                  <div className="space-y-2">
                    <Label htmlFor="bio">Bio</Label>
                    <Textarea
                      id="bio"
                      placeholder="Décrivez-vous en quelques mots..."
                      value={profilForm.bio}
                      onChange={(e) => setProfilForm({ ...profilForm, bio: e.target.value })}
                      rows={4}
                    />
                  </div>

                  <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                      <Label htmlFor="niveau">Niveau</Label>
                      <Select
                        value={profilForm.niveau}
                        onValueChange={(value) => setProfilForm({ ...profilForm, niveau: value })}
                      >
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="debutant">Débutant</SelectItem>
                          <SelectItem value="intermediaire">Intermédiaire</SelectItem>
                          <SelectItem value="avance">Avancé</SelectItem>
                          <SelectItem value="expert">Expert</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>

                    <div className="space-y-2">
                      <Label>
                        <MapPin className="h-4 w-4 inline mr-2" />
                        Localisation
                      </Label>
                      <div className="flex gap-2">
                        <Input
                          placeholder="Ville"
                          value={profilForm.ville}
                          onChange={(e) => setProfilForm({ ...profilForm, ville: e.target.value })}
                        />
                        <Input
                          placeholder="Pays"
                          value={profilForm.pays}
                          onChange={(e) => setProfilForm({ ...profilForm, pays: e.target.value })}
                        />
                      </div>
                    </div>
                  </div>

                  <div className="space-y-2">
                    <Label>Technologies</Label>
                    <div className="flex gap-2">
                      <Input
                        placeholder="Ajouter une technologie"
                        value={newTech}
                        onChange={(e) => setNewTech(e.target.value)}
                        onKeyPress={(e) => e.key === "Enter" && (e.preventDefault(), addTechnology())}
                      />
                      <Button type="button" onClick={addTechnology} variant="outline">
                        Ajouter
                      </Button>
                    </div>
                    <div className="flex flex-wrap gap-2 mt-2">
                      {profilForm.technologies.map((tech) => (
                        <Badge key={tech} variant="secondary" className="cursor-pointer" onClick={() => removeTechnology(tech)}>
                          {tech} ×
                        </Badge>
                      ))}
                    </div>
                  </div>

                  <Separator />

                  <div className="space-y-4">
                    <h4 className="font-medium">Liens</h4>
                    <div className="space-y-2">
                      <Label htmlFor="github">
                        <Github className="h-4 w-4 inline mr-2" />
                        GitHub
                      </Label>
                      <Input
                        id="github"
                        type="url"
                        placeholder="https://github.com/username"
                        value={profilForm.github_url}
                        onChange={(e) => setProfilForm({ ...profilForm, github_url: e.target.value })}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="linkedin">
                        <Linkedin className="h-4 w-4 inline mr-2" />
                        LinkedIn
                      </Label>
                      <Input
                        id="linkedin"
                        type="url"
                        placeholder="https://linkedin.com/in/username"
                        value={profilForm.linkedin_url}
                        onChange={(e) => setProfilForm({ ...profilForm, linkedin_url: e.target.value })}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="portfolio">
                        <Globe className="h-4 w-4 inline mr-2" />
                        Portfolio
                      </Label>
                      <Input
                        id="portfolio"
                        type="url"
                        placeholder="https://monportfolio.com"
                        value={profilForm.portfolio_url}
                        onChange={(e) => setProfilForm({ ...profilForm, portfolio_url: e.target.value })}
                      />
                    </div>
                  </div>

                  {utilisateur?.role === "mentor" && (
                    <>
                      <Separator />
                      <div className="flex items-center justify-between">
                        <div>
                          <Label>Disponible pour le mentorat</Label>
                          <p className="text-sm text-muted-foreground">
                            Permettre aux étudiants de vous contacter
                          </p>
                        </div>
                        <Switch
                          checked={profilForm.est_disponible_mentorat}
                          onCheckedChange={(checked) => 
                            setProfilForm({ ...profilForm, est_disponible_mentorat: checked })
                          }
                        />
                      </div>
                    </>
                  )}

                  <Button type="submit" disabled={isSaving}>
                    {isSaving ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : <Save className="h-4 w-4 mr-2" />}
                    Enregistrer
                  </Button>
                </form>
              </TabsContent>

              {/* Onglet Sécurité */}
              <TabsContent value="securite" className="space-y-4">
                <form onSubmit={handleUpdatePassword} className="space-y-4">
                  <CardDescription>
                    <Lock className="h-4 w-4 inline mr-2" />
                    Changez votre mot de passe pour sécuriser votre compte
                  </CardDescription>

                  <div className="space-y-2">
                    <Label htmlFor="current-password">Mot de passe actuel</Label>
                    <Input
                      id="current-password"
                      type="password"
                      value={passwordForm.mot_de_passe_actuel}
                      onChange={(e) => setPasswordForm({ ...passwordForm, mot_de_passe_actuel: e.target.value })}
                    />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="new-password">Nouveau mot de passe</Label>
                    <Input
                      id="new-password"
                      type="password"
                      value={passwordForm.mot_de_passe}
                      onChange={(e) => setPasswordForm({ ...passwordForm, mot_de_passe: e.target.value })}
                    />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="confirm-password">Confirmer le mot de passe</Label>
                    <Input
                      id="confirm-password"
                      type="password"
                      value={passwordForm.mot_de_passe_confirmation}
                      onChange={(e) => setPasswordForm({ ...passwordForm, mot_de_passe_confirmation: e.target.value })}
                    />
                  </div>

                  <Button type="submit" disabled={isSaving}>
                    {isSaving ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : <Lock className="h-4 w-4 mr-2" />}
                    Changer le mot de passe
                  </Button>
                </form>
              </TabsContent>
            </CardContent>
          </Tabs>
        </Card>
      </div>
    </div>
  );
}
