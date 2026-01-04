"use client";

import React, { useState, useEffect, useCallback } from "react";
import { toast } from "sonner";
import { apiClient } from "@/lib/api";
import {
  Loader2,
  Plus,
  Search,
  MoreHorizontal,
  UserCheck,
  UserX,
  Trash2,
  Edit,
  Key,
  Shield,
  Users,
  GraduationCap,
  UserCog,
  Activity,
  TrendingUp,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
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
  DialogTrigger,
} from "@/components/ui/dialog";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
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
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip";
import { Separator } from "@/components/ui/separator";
import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationLink,
  PaginationNext,
  PaginationPrevious,
} from "@/components/ui/pagination";

interface Utilisateur {
  id: string;
  prenom: string;
  nom: string;
  email: string;
  role: string;
  avatar: string | null;
  est_actif: boolean;
  niveau: number;
  points: number;
  created_at: string;
}

interface UtilisateursData {
  utilisateurs: Utilisateur[];
  statistiques: {
    total: number;
    etudiants: number;
    mentors: number;
    administrateurs: number;
    actifs: number;
  };
  meta: {
    total: number;
    par_page: number;
    page_courante: number;
    derniere_page: number;
  };
}

interface ApiResponse {
  success: boolean;
  data: UtilisateursData;
}

interface CreateUserForm {
  prenom: string;
  nom: string;
  email: string;
  role: string;
  est_actif: boolean;
}

const initialFormState: CreateUserForm = {
  prenom: "",
  nom: "",
  email: "",
  role: "etudiant",
  est_actif: true,
};

export default function UtilisateursPage() {
  const [data, setData] = useState<UtilisateursData | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);

  // Filtres
  const [searchQuery, setSearchQuery] = useState("");
  const [roleFilter, setRoleFilter] = useState<string>("all");
  const [statusFilter, setStatusFilter] = useState<string>("all");
  const [currentPage, setCurrentPage] = useState(1);

  // Dialogs
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [isEditOpen, setIsEditOpen] = useState(false);
  const [isDeleteOpen, setIsDeleteOpen] = useState(false);
  const [isRoleOpen, setIsRoleOpen] = useState(false);
  const [selectedUser, setSelectedUser] = useState<Utilisateur | null>(null);

  // Formulaires
  const [createForm, setCreateForm] = useState<CreateUserForm>(initialFormState);
  const [editForm, setEditForm] = useState({ prenom: "", nom: "", email: "" });
  const [newRole, setNewRole] = useState("");

  const fetchUtilisateurs = useCallback(async () => {
    setIsLoading(true);
    try {
      const params = new URLSearchParams();
      if (searchQuery) params.append("recherche", searchQuery);
      if (roleFilter !== "all") params.append("role", roleFilter);
      if (statusFilter !== "all") params.append("est_actif", statusFilter);
      params.append("page", currentPage.toString());
      params.append("per_page", "5");

      const response = await apiClient.get<ApiResponse>(
        `/v1/admin/utilisateurs?${params.toString()}`
      );
      setData(response.data);
    } catch (error) {
      console.error("Erreur lors du chargement:", error);
    } finally {
      setIsLoading(false);
    }
  }, [searchQuery, roleFilter, statusFilter, currentPage]);

  useEffect(() => {
    fetchUtilisateurs();
  }, [fetchUtilisateurs]);

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSaving(true);

    try {
      const generatedPassword = `${createForm.nom}devbridge`;
      await apiClient.post("/v1/admin/utilisateurs", {
        ...createForm,
        mot_de_passe: generatedPassword,
        mot_de_passe_confirmation: generatedPassword,
      });
      toast.success(`Utilisateur créé avec succès. Mot de passe: ${generatedPassword}`);
      setIsCreateOpen(false);
      setCreateForm(initialFormState);
      fetchUtilisateurs();
    } catch (error: unknown) {
      const err = error as { message?: string };
      toast.error(err.message || "Erreur lors de la création");
    } finally {
      setIsSaving(false);
    }
  };

  const handleEdit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedUser) return;

    setIsSaving(true);

    try {
      await apiClient.put(`/v1/admin/utilisateurs/${selectedUser.id}`, editForm);
      toast.success("Utilisateur modifié avec succès");
      setIsEditOpen(false);
      fetchUtilisateurs();
    } catch (error: unknown) {
      const err = error as { message?: string };
      toast.error(err.message || "Erreur lors de la modification");
    } finally {
      setIsSaving(false);
    }
  };

  const handleDelete = async () => {
    if (!selectedUser) return;

    setIsSaving(true);
    try {
      await apiClient.delete(`/v1/admin/utilisateurs/${selectedUser.id}`);
      toast.success("Utilisateur supprimé avec succès");
      setIsDeleteOpen(false);
      fetchUtilisateurs();
    } catch (error: unknown) {
      const err = error as { message?: string };
      toast.error(err.message || "Erreur lors de la suppression");
    } finally {
      setIsSaving(false);
    }
  };

  const handleToggleStatus = async (user: Utilisateur) => {
    try {
      const endpoint = user.est_actif
        ? `/v1/admin/utilisateurs/${user.id}/desactiver`
        : `/v1/admin/utilisateurs/${user.id}/reactiver`;
      
      await apiClient.post(endpoint);
      toast.success(user.est_actif ? "Utilisateur désactivé" : "Utilisateur réactivé");
      fetchUtilisateurs();
    } catch (error: unknown) {
      const err = error as { message?: string };
      toast.error(err.message || "Erreur");
    }
  };

  const handleChangeRole = async () => {
    if (!selectedUser || !newRole) return;

    setIsSaving(true);
    try {
      await apiClient.post(`/v1/admin/utilisateurs/${selectedUser.id}/changer-role`, {
        role: newRole,
      });
      toast.success("Rôle modifié avec succès");
      setIsRoleOpen(false);
      fetchUtilisateurs();
    } catch (error: unknown) {
      const err = error as { message?: string };
      toast.error(err.message || "Erreur lors du changement de rôle");
    } finally {
      setIsSaving(false);
    }
  };

  const handleResetPassword = async (user: Utilisateur) => {
    try {
      await apiClient.post(`/v1/admin/utilisateurs/${user.id}/reinitialiser-mot-de-passe`);
      toast.success("Email de réinitialisation envoyé");
    } catch (error: unknown) {
      const err = error as { message?: string };
      toast.error(err.message || "Erreur");
    }
  };

  const openEditDialog = (user: Utilisateur) => {
    setSelectedUser(user);
    setEditForm({ prenom: user.prenom, nom: user.nom, email: user.email });
    setIsEditOpen(true);
  };

  const openRoleDialog = (user: Utilisateur) => {
    setSelectedUser(user);
    setNewRole(user.role);
    setIsRoleOpen(true);
  };

  const openDeleteDialog = (user: Utilisateur) => {
    setSelectedUser(user);
    setIsDeleteOpen(true);
  };

  const getInitials = (prenom: string, nom: string) => {
    return `${prenom.charAt(0)}${nom.charAt(0)}`.toUpperCase();
  };

  const getRoleBadge = (role: string) => {
    const variants: Record<string, "default" | "secondary" | "destructive"> = {
      administrateur: "destructive",
      mentor: "default",
      etudiant: "secondary",
    };
    const labels: Record<string, string> = {
      administrateur: "Admin",
      mentor: "Mentor",
      etudiant: "Étudiant",
    };
    return <Badge variant={variants[role] || "secondary"}>{labels[role] || role}</Badge>;
  };

  if (isLoading && !data) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <Loader2 className="h-8 w-8 animate-spin" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Gestion des utilisateurs</h1>
        </div>
        <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
          <DialogTrigger asChild>
            <Button>
              <Plus className="h-4 w-4 mr-2" />
              Nouvel utilisateur
            </Button>
          </DialogTrigger>
          <DialogContent className="sm:max-w-[500px]">
            <DialogHeader>
              <DialogTitle>Créer un utilisateur</DialogTitle>
              <DialogDescription>
                Remplissez les informations pour créer un nouveau compte
              </DialogDescription>
            </DialogHeader>
            <form onSubmit={handleCreate}>
              <div className="grid gap-4 py-4">
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="prenom">Prénom</Label>
                    <Input
                      id="prenom"
                      value={createForm.prenom}
                      onChange={(e) => setCreateForm({ ...createForm, prenom: e.target.value })}
                      required
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="nom">Nom</Label>
                    <Input
                      id="nom"
                      value={createForm.nom}
                      onChange={(e) => setCreateForm({ ...createForm, nom: e.target.value })}
                      required
                    />
                  </div>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="email">Email</Label>
                  <Input
                    id="email"
                    type="email"
                    value={createForm.email}
                    onChange={(e) => setCreateForm({ ...createForm, email: e.target.value })}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="role">Rôle</Label>
                  <Select
                    value={createForm.role}
                    onValueChange={(value) => setCreateForm({ ...createForm, role: value })}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="etudiant">Étudiant</SelectItem>
                      <SelectItem value="mentor">Mentor</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
              <DialogFooter>
                <Button type="button" variant="outline" onClick={() => setIsCreateOpen(false)}>
                  Annuler
                </Button>
                <Button type="submit" disabled={isSaving}>
                  {isSaving && <Loader2 className="h-4 w-4 animate-spin mr-2" />}
                  Créer
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {/* Statistiques */}
      {data?.statistiques && (
        <div className="grid gap-3 grid-cols-2 md:grid-cols-5">
          <Card className="border-l-4 border-l-blue-500 py-3">
            <CardContent className="flex items-center gap-3 p-0 px-4">
              <div className="p-2 rounded-full bg-blue-500/10">
                <Users className="h-4 w-4 text-blue-500" />
              </div>
              <div>
                <p className="text-2xl font-bold">{data.statistiques.total}</p>
                <p className="text-xs text-muted-foreground">Total</p>
              </div>
            </CardContent>
          </Card>
          <Card className="border-l-4 border-l-green-500 py-3">
            <CardContent className="flex items-center gap-3 p-0 px-4">
              <div className="p-2 rounded-full bg-green-500/10">
                <GraduationCap className="h-4 w-4 text-green-500" />
              </div>
              <div>
                <p className="text-2xl font-bold">{data.statistiques.etudiants}</p>
                <p className="text-xs text-muted-foreground">Étudiants</p>
              </div>
            </CardContent>
          </Card>
          <Card className="border-l-4 border-l-purple-500 py-3">
            <CardContent className="flex items-center gap-3 p-0 px-4">
              <div className="p-2 rounded-full bg-purple-500/10">
                <UserCog className="h-4 w-4 text-purple-500" />
              </div>
              <div>
                <p className="text-2xl font-bold">{data.statistiques.mentors}</p>
                <p className="text-xs text-muted-foreground">Mentors</p>
              </div>
            </CardContent>
          </Card>
          <Card className="border-l-4 border-l-red-500 py-3">
            <CardContent className="flex items-center gap-3 p-0 px-4">
              <div className="p-2 rounded-full bg-red-500/10">
                <Shield className="h-4 w-4 text-red-500" />
              </div>
              <div>
                <p className="text-2xl font-bold">{data.statistiques.administrateurs}</p>
                <p className="text-xs text-muted-foreground">Admins</p>
              </div>
            </CardContent>
          </Card>
          <Card className="border-l-4 border-l-emerald-500 py-3">
            <CardContent className="flex items-center gap-3 p-0 px-4">
              <div className="p-2 rounded-full bg-emerald-500/10">
                <Activity className="h-4 w-4 text-emerald-500" />
              </div>
              <div>
                <p className="text-2xl font-bold">{data.statistiques.actifs}</p>
                <p className="text-xs text-muted-foreground">Actifs</p>
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Filtres */}
      <div className="flex flex-col md:flex-row gap-3">
        <div className="flex-1 relative">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Rechercher par nom ou email..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="pl-10"
          />
        </div>
        <Select value={roleFilter} onValueChange={setRoleFilter}>
          <SelectTrigger className="w-full md:w-40">
            <SelectValue placeholder="Rôle" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">Tous les rôles</SelectItem>
            <SelectItem value="etudiant">Étudiants</SelectItem>
            <SelectItem value="mentor">Mentors</SelectItem>
            <SelectItem value="administrateur">Administrateurs</SelectItem>
          </SelectContent>
        </Select>
        <Select value={statusFilter} onValueChange={setStatusFilter}>
          <SelectTrigger className="w-full md:w-40">
            <SelectValue placeholder="Statut" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">Tous les statuts</SelectItem>
            <SelectItem value="true">Actifs</SelectItem>
            <SelectItem value="false">Inactifs</SelectItem>
          </SelectContent>
        </Select>
      </div>

      {/* Table */}
      <Card className="overflow-hidden">
        <CardHeader className="py-4 px-6 bg-muted/30">
          <div className="flex items-center justify-between">
            <div>
              <CardTitle className="text-lg">Liste des utilisateurs</CardTitle>
              <CardDescription className="text-xs mt-0.5">
                {data?.meta?.total || 0} utilisateur{(data?.meta?.total || 0) > 1 ? "s" : ""} trouvé{(data?.meta?.total || 0) > 1 ? "s" : ""}
              </CardDescription>
            </div>
          </div>
        </CardHeader>
        <CardContent className="p-0">
          <TooltipProvider>
            <Table>
              <TableHeader>
                <TableRow className="bg-muted/40 hover:bg-muted/40">
                  <TableHead className="font-semibold text-xs uppercase tracking-wide">Utilisateur</TableHead>
                  <TableHead className="font-semibold text-xs uppercase tracking-wide">Email</TableHead>
                  <TableHead className="font-semibold text-xs uppercase tracking-wide">Rôle</TableHead>
                  <TableHead className="font-semibold text-xs uppercase tracking-wide">Statut</TableHead>
                  <TableHead className="font-semibold text-xs uppercase tracking-wide text-center">Niveau</TableHead>
                  <TableHead className="font-semibold text-xs uppercase tracking-wide text-center">Points</TableHead>
                  <TableHead className="text-right font-semibold text-xs uppercase tracking-wide">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data?.utilisateurs?.length === 0 && (
                  <TableRow>
                    <TableCell colSpan={7} className="text-center py-12 text-muted-foreground">
                      <Users className="h-12 w-12 mx-auto mb-3 opacity-10" />
                      <p className="font-medium">Aucun utilisateur trouvé</p>
                      <p className="text-xs mt-1">Essayez de modifier vos filtres</p>
                    </TableCell>
                  </TableRow>
                )}
                {data?.utilisateurs?.map((user) => (
                  <TableRow key={user.id} className="group hover:bg-muted/30 transition-colors">
                    <TableCell className="py-3">
                      <div className="flex items-center gap-3">
                        <Avatar className="h-9 w-9 ring-2 ring-background">
                          <AvatarImage src={user.avatar || undefined} />
                          <AvatarFallback className="bg-gradient-to-br from-primary/20 to-primary/10 text-primary text-sm font-semibold">
                            {getInitials(user.prenom, user.nom)}
                          </AvatarFallback>
                        </Avatar>
                        <div className="min-w-0">
                          <p className="font-medium text-sm truncate">{user.prenom} {user.nom}</p>
                          <p className="text-[10px] text-muted-foreground font-mono">#{user.id}</p>
                        </div>
                      </div>
                    </TableCell>
                    <TableCell className="py-3">
                      <span className="text-sm text-muted-foreground">{user.email}</span>
                    </TableCell>
                    <TableCell className="py-3">{getRoleBadge(user.role)}</TableCell>
                    <TableCell className="py-3">
                      <Badge 
                        variant="outline" 
                        className={`text-xs ${user.est_actif 
                          ? "border-green-500/50 bg-green-500/10 text-green-600" 
                          : "border-gray-400/50 bg-gray-400/10 text-gray-500"}`}
                      >
                        <span className={`mr-1.5 h-1.5 w-1.5 rounded-full inline-block ${user.est_actif ? "bg-green-500" : "bg-gray-400"}`} />
                        {user.est_actif ? "Actif" : "Inactif"}
                      </Badge>
                    </TableCell>
                    <TableCell className="py-3 text-center">
                      <Tooltip>
                        <TooltipTrigger>
                          <span className="inline-flex items-center justify-center h-7 w-7 rounded-md bg-muted text-xs font-bold">
                            {user.niveau}
                          </span>
                        </TooltipTrigger>
                        <TooltipContent>Niveau {user.niveau}</TooltipContent>
                      </Tooltip>
                    </TableCell>
                    <TableCell className="py-3 text-center">
                      <Tooltip>
                        <TooltipTrigger>
                          <span className="inline-flex items-center gap-1 text-sm font-semibold text-amber-600">
                            <TrendingUp className="h-3 w-3" />
                            {user.points}
                          </span>
                        </TooltipTrigger>
                        <TooltipContent>{user.points} points</TooltipContent>
                      </Tooltip>
                    </TableCell>
                    <TableCell className="py-3 text-right">
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button variant="ghost" size="icon" className="h-8 w-8 opacity-0 group-hover:opacity-100 transition-opacity">
                            <MoreHorizontal className="h-4 w-4" />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-48">
                          <DropdownMenuLabel>Actions</DropdownMenuLabel>
                          <DropdownMenuSeparator />
                          <DropdownMenuItem onClick={() => openEditDialog(user)}>
                            <Edit className="h-4 w-4 mr-2" />
                            Modifier
                          </DropdownMenuItem>
                          <DropdownMenuItem onClick={() => openRoleDialog(user)}>
                            <Shield className="h-4 w-4 mr-2" />
                            Changer le rôle
                          </DropdownMenuItem>
                          <DropdownMenuItem onClick={() => handleResetPassword(user)}>
                            <Key className="h-4 w-4 mr-2" />
                            Réinitialiser MDP
                          </DropdownMenuItem>
                          <DropdownMenuSeparator />
                          <DropdownMenuItem onClick={() => handleToggleStatus(user)}>
                            {user.est_actif ? (
                              <>
                                <UserX className="h-4 w-4 mr-2" />
                                Désactiver
                              </>
                            ) : (
                              <>
                                <UserCheck className="h-4 w-4 mr-2" />
                                Réactiver
                              </>
                            )}
                          </DropdownMenuItem>
                          <DropdownMenuItem
                            onClick={() => openDeleteDialog(user)}
                            className="text-red-600 focus:text-red-600"
                          >
                            <Trash2 className="h-4 w-4 mr-2" />
                            Supprimer
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </TooltipProvider>
        </CardContent>
      </Card>

      {/* Pagination */}
      {data?.meta && data.meta.derniere_page > 1 && (
        <div className="flex items-center justify-between">
          <p className="text-sm text-muted-foreground">
            Page {data.meta.page_courante} sur {data.meta.derniere_page} ({data.meta.total} résultats)
          </p>
          <Pagination>
            <PaginationContent>
              <PaginationItem>
                <PaginationPrevious
                  href="#"
                  onClick={(e) => {
                    e.preventDefault();
                    if (currentPage > 1) setCurrentPage((p) => p - 1);
                  }}
                  className={currentPage === 1 ? "pointer-events-none opacity-50" : "cursor-pointer"}
                />
              </PaginationItem>
              {Array.from({ length: data.meta.derniere_page }, (_, i) => i + 1)
                .filter((page) => {
                  const current = data.meta.page_courante;
                  return page === 1 || page === data.meta.derniere_page || Math.abs(page - current) <= 1;
                })
                .map((page, index, array) => (
                  <React.Fragment key={page}>
                    {index > 0 && array[index - 1] !== page - 1 && (
                      <PaginationItem>
                        <PaginationEllipsis />
                      </PaginationItem>
                    )}
                    <PaginationItem>
                      <PaginationLink
                        href="#"
                        isActive={currentPage === page}
                        onClick={(e) => {
                          e.preventDefault();
                          setCurrentPage(page);
                        }}
                        className="cursor-pointer"
                      >
                        {page}
                      </PaginationLink>
                    </PaginationItem>
                  </React.Fragment>
                ))}
              <PaginationItem>
                <PaginationNext
                  href="#"
                  onClick={(e) => {
                    e.preventDefault();
                    if (currentPage < data.meta.derniere_page) setCurrentPage((p) => p + 1);
                  }}
                  className={currentPage === data.meta.derniere_page ? "pointer-events-none opacity-50" : "cursor-pointer"}
                />
              </PaginationItem>
            </PaginationContent>
          </Pagination>
        </div>
      )}

      {/* Edit Dialog */}
      <Dialog open={isEditOpen} onOpenChange={setIsEditOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Modifier l&apos;utilisateur</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleEdit}>
            <div className="grid gap-4 py-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Prénom</Label>
                  <Input
                    value={editForm.prenom}
                    onChange={(e) => setEditForm({ ...editForm, prenom: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Nom</Label>
                  <Input
                    value={editForm.nom}
                    onChange={(e) => setEditForm({ ...editForm, nom: e.target.value })}
                  />
                </div>
              </div>
              <div className="space-y-2">
                <Label>Email</Label>
                <Input
                  type="email"
                  value={editForm.email}
                  onChange={(e) => setEditForm({ ...editForm, email: e.target.value })}
                />
              </div>
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setIsEditOpen(false)}>
                Annuler
              </Button>
              <Button type="submit" disabled={isSaving}>
                {isSaving && <Loader2 className="h-4 w-4 animate-spin mr-2" />}
                Enregistrer
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Role Dialog */}
      <Dialog open={isRoleOpen} onOpenChange={setIsRoleOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Changer le rôle</DialogTitle>
            <DialogDescription>
              Modifier le rôle de {selectedUser?.prenom} {selectedUser?.nom}
            </DialogDescription>
          </DialogHeader>
          <div className="py-4">
            <Select value={newRole} onValueChange={setNewRole}>
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="etudiant">Étudiant</SelectItem>
                <SelectItem value="mentor">Mentor</SelectItem>
                <SelectItem value="administrateur">Administrateur</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => setIsRoleOpen(false)}>
              Annuler
            </Button>
            <Button onClick={handleChangeRole} disabled={isSaving}>
              {isSaving && <Loader2 className="h-4 w-4 animate-spin mr-2" />}
              Confirmer
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Delete Dialog */}
      <Dialog open={isDeleteOpen} onOpenChange={setIsDeleteOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Confirmer la suppression</DialogTitle>
            <DialogDescription>
              Êtes-vous sûr de vouloir supprimer {selectedUser?.prenom} {selectedUser?.nom} ? Cette
              action est irréversible.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => setIsDeleteOpen(false)}>
              Annuler
            </Button>
            <Button variant="destructive" onClick={handleDelete} disabled={isSaving}>
              {isSaving && <Loader2 className="h-4 w-4 animate-spin mr-2" />}
              Supprimer
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
