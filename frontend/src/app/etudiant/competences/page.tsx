"use client";
import { useEffect, useState } from "react";
import { apiClient } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Plus, Zap } from "lucide-react";

export default function EtudiantCompetencesPage() {
  const [competences, setCompetences] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [form, setForm] = useState({ nom: "", niveau: "debutant" });
  const [submitting, setSubmitting] = useState(false);

  async function fetchCompetences() {
    setLoading(true);
    try {
      const res = await apiClient.get<any>("/v1/etudiant/profil/competences");
      setCompetences(res.data?.competences || []);
    } catch (e: any) {
      setError(e.message || "Erreur lors du chargement");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    fetchCompetences();
  }, []);

  async function handleAddCompetence(e: React.FormEvent) {
    e.preventDefault();
    setSubmitting(true);
    try {
      await apiClient.post("/v1/etudiant/profil/competences", form);
      setForm({ nom: "", niveau: "debutant" });
      setDialogOpen(false);
      fetchCompetences();
    } catch (e: any) {
      alert(e.message || "Erreur lors de l'ajout");
    } finally {
      setSubmitting(false);
    }
  }

  const niveauColors: Record<string, string> = {
    debutant: "bg-green-100 text-green-800",
    intermediaire: "bg-blue-100 text-blue-800",
    avance: "bg-purple-100 text-purple-800",
    expert: "bg-orange-100 text-orange-800",
  };

  if (loading) return <div className="py-12 text-center">Chargement...</div>;
  if (error) return <div className="py-12 text-center text-red-600">{error}</div>;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Mes compétences</h1>
        <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
          <DialogTrigger asChild>
            <Button><Plus className="h-4 w-4 mr-2" /> Ajouter</Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Ajouter une compétence</DialogTitle>
            </DialogHeader>
            <form onSubmit={handleAddCompetence} className="space-y-4">
              <div>
                <Label htmlFor="nom">Nom de la compétence</Label>
                <Input
                  id="nom"
                  value={form.nom}
                  onChange={(e) => setForm({ ...form, nom: e.target.value })}
                  placeholder="Ex: JavaScript, Python, React..."
                  required
                />
              </div>
              <div>
                <Label htmlFor="niveau">Niveau</Label>
                <Select value={form.niveau} onValueChange={(v) => setForm({ ...form, niveau: v })}>
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
              <Button type="submit" disabled={submitting} className="w-full">
                {submitting ? "Ajout..." : "Ajouter la compétence"}
              </Button>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {competences.length === 0 ? (
          <div className="text-gray-500 col-span-full">
            Aucune compétence ajoutée. Cliquez sur &quot;Ajouter&quot; pour commencer.
          </div>
        ) : (
          competences.map((c: any) => (
            <Card key={c.id}>
              <CardHeader className="flex flex-row items-center gap-3 pb-2">
                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100">
                  <Zap className="h-4 w-4 text-blue-600" />
                </div>
                <CardTitle className="text-base">{c.nom}</CardTitle>
              </CardHeader>
              <CardContent>
                <Badge className={niveauColors[c.niveau] || "bg-gray-100 text-gray-800"}>
                  {c.niveau || "Non défini"}
                </Badge>
                {c.validee !== undefined && (
                  <p className="text-xs text-gray-400 mt-2">
                    {c.validee ? "✓ Validée par un mentor" : "En attente de validation"}
                  </p>
                )}
                {c.valide_a && (
                  <p className="text-xs text-gray-400 mt-1">
                    Validée le : {new Date(c.valide_a).toLocaleDateString()}
                  </p>
                )}
              </CardContent>
            </Card>
          ))
        )}
      </div>
    </div>
  );
}
