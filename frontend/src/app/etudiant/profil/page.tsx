"use client";
import { useEffect, useState } from "react";
import { apiClient } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Separator } from "@/components/ui/separator";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Badge } from "@/components/ui/badge";
import { Loader2, User, Pencil, Save, Upload, X } from "lucide-react";

export default function EtudiantProfilPage() {
  const [profil, setProfil] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [editing, setEditing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [form, setForm] = useState({ prenom: "", nom: "", bio: "", site_web: "", github: "", linkedin: "" });
  const [avatarFile, setAvatarFile] = useState<File | null>(null);
  const [competences, setCompetences] = useState<any[]>([]);
  const [newCompetence, setNewCompetence] = useState("");

  useEffect(() => {
    async function fetchData() {
      setLoading(true);
      try {
        const [profilRes, compRes] = await Promise.all([
          apiClient.get<any>("/v1/etudiant/profil"),
          apiClient.get<any>("/v1/etudiant/profil/competences").catch(() => ({ data: {} })),
        ]);
        const u = profilRes.data?.utilisateur || profilRes.data || {};
        setProfil(u);
        setForm({
          prenom: u.prenom || "", nom: u.nom || "",
          bio: u.profil?.bio || u.bio || "",
          site_web: u.profil?.site_web || u.site_web || "",
          github: u.profil?.github || u.github || "",
          linkedin: u.profil?.linkedin || u.linkedin || "",
        });
        setCompetences(compRes.data?.competences || compRes.data || []);
      } catch (e: any) { setError(e.message); }
      setLoading(false);
    }
    fetchData();
  }, []);

  const handleSave = async () => {
    setSaving(true);
    try {
      await apiClient.put("/v1/etudiant/profil", form);
      if (avatarFile) {
        const fd = new FormData();
        fd.append("avatar", avatarFile);
        await apiClient.upload("/v1/etudiant/profil/avatar", fd);
      }
      const res = await apiClient.get<any>("/v1/etudiant/profil");
      setProfil(res.data?.utilisateur || res.data || {});
      setEditing(false);
      setAvatarFile(null);
    } catch (e: any) { setError(e.message); }
    setSaving(false);
  };

  const handleAddCompetence = async () => {
    if (!newCompetence.trim()) return;
    try {
      await apiClient.post("/v1/etudiant/profil/competences", { nom: newCompetence });
      setNewCompetence("");
      const res = await apiClient.get<any>("/v1/etudiant/profil/competences");
      setCompetences(res.data?.competences || res.data || []);
    } catch (e: any) { setError(e.message); }
  };

  if (loading) return <div className="py-12 text-center"><Loader2 className="h-8 w-8 animate-spin mx-auto" /></div>;

  return (
    <div className="space-y-6 max-w-3xl">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold flex items-center gap-2"><User className="h-6 w-6" /> Mon profil</h1>
        {!editing && <Button variant="outline" onClick={() => setEditing(true)}><Pencil className="h-4 w-4 mr-2" /> Modifier</Button>}
      </div>
      {error && <div className="text-red-600 text-sm bg-red-50 p-3 rounded-lg">{error}</div>}

      {!profil ? <p className="text-muted-foreground">Aucune donnée.</p> : (
        <Card>
          <CardContent className="p-6 space-y-6">
            {/* Avatar */}
            <div className="flex items-center gap-4">
              <Avatar className="h-20 w-20">
                <AvatarImage src={profil.avatar || profil.profil?.avatar} />
                <AvatarFallback className="text-2xl">{profil.prenom?.[0]}{profil.nom?.[0]}</AvatarFallback>
              </Avatar>
              <div>
                {editing ? (
                  <div className="space-y-2">
                    <div className="grid grid-cols-2 gap-2"><div><Label>Prénom</Label><Input value={form.prenom} onChange={e => setForm({ ...form, prenom: e.target.value })} /></div><div><Label>Nom</Label><Input value={form.nom} onChange={e => setForm({ ...form, nom: e.target.value })} /></div></div>
                    <div><Label>Avatar</Label><Input type="file" accept="image/*" onChange={e => setAvatarFile(e.target.files?.[0] || null)} /></div>
                  </div>
                ) : (
                  <div>
                    <h2 className="text-xl font-bold">{profil.prenom} {profil.nom}</h2>
                    <p className="text-sm text-muted-foreground">{profil.email}</p>
                    <div className="flex gap-2 mt-1">
                      {profil.niveau && <Badge variant="secondary">{profil.niveau}</Badge>}
                      {profil.points !== undefined && <Badge variant="outline">{profil.points} pts</Badge>}
                    </div>
                  </div>
                )}
              </div>
            </div>

            <Separator />

            {/* Bio & Liens */}
            {editing ? (
              <div className="space-y-4">
                <div><Label>Bio</Label><Textarea value={form.bio} onChange={e => setForm({ ...form, bio: e.target.value })} rows={3} /></div>
                <div><Label>Site web</Label><Input value={form.site_web} onChange={e => setForm({ ...form, site_web: e.target.value })} placeholder="https://..." /></div>
                <div><Label>GitHub</Label><Input value={form.github} onChange={e => setForm({ ...form, github: e.target.value })} placeholder="https://github.com/..." /></div>
                <div><Label>LinkedIn</Label><Input value={form.linkedin} onChange={e => setForm({ ...form, linkedin: e.target.value })} placeholder="https://linkedin.com/in/..." /></div>
                <div className="flex gap-2">
                  <Button onClick={handleSave} disabled={saving}>{saving ? <Loader2 className="h-4 w-4 animate-spin" /> : <><Save className="h-4 w-4 mr-2" /> Enregistrer</>}</Button>
                  <Button variant="outline" onClick={() => setEditing(false)}><X className="h-4 w-4 mr-2" /> Annuler</Button>
                </div>
              </div>
            ) : (
              <div className="space-y-3 text-sm">
                {(profil.profil?.bio || profil.bio) && <div><span className="font-semibold">Bio :</span> {profil.profil?.bio || profil.bio}</div>}
                {(profil.profil?.site_web || profil.site_web) && <div><span className="font-semibold">Site web :</span> <a href={profil.profil?.site_web || profil.site_web} target="_blank" className="text-blue-600 underline">{profil.profil?.site_web || profil.site_web}</a></div>}
                {(profil.profil?.github || profil.github) && <div><span className="font-semibold">GitHub :</span> <a href={profil.profil?.github || profil.github} target="_blank" className="text-blue-600 underline">{profil.profil?.github || profil.github}</a></div>}
                {(profil.profil?.linkedin || profil.linkedin) && <div><span className="font-semibold">LinkedIn :</span> <a href={profil.profil?.linkedin || profil.linkedin} target="_blank" className="text-blue-600 underline">{profil.profil?.linkedin || profil.linkedin}</a></div>}
              </div>
            )}

            <Separator />

            {/* Competences */}
            <div>
              <h3 className="font-semibold mb-3">Compétences</h3>
              <div className="flex flex-wrap gap-2 mb-3">
                {competences.length === 0 ? <p className="text-sm text-muted-foreground">Aucune compétence.</p> : competences.map((c: any) => (
                  <Badge key={c.id} variant="secondary">{c.nom} {c.pivot?.niveau_maitrise ? `(Niv. ${c.pivot.niveau_maitrise})` : ""}</Badge>
                ))}
              </div>
              <div className="flex gap-2">
                <Input placeholder="Ajouter une compétence..." value={newCompetence} onChange={e => setNewCompetence(e.target.value)} className="max-w-xs" onKeyDown={e => e.key === "Enter" && handleAddCompetence()} />
                <Button size="sm" onClick={handleAddCompetence} disabled={!newCompetence.trim()}>Ajouter</Button>
              </div>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
