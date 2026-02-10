"use client";
import { useEffect, useState } from "react";
import { apiClient } from "@/lib/api";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { Loader2, User, Pencil, Save, X } from "lucide-react";

export default function MentorProfilPage() {
  const [profil, setProfil] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [editing, setEditing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [form, setForm] = useState({ bio: "", specialites: "", site_web: "", github: "", linkedin: "" });

  useEffect(() => {
    async function fetch() {
      setLoading(true);
      try {
        const res = await apiClient.get<any>("/v1/mentor/profil");
        const p = res.data?.mentor || res.data?.utilisateur || res.data || {};
        setProfil(p);
        setForm({
          bio: p.profil?.bio || p.bio || "",
          specialites: Array.isArray(p.specialites) ? p.specialites.join(", ") : (p.profil?.specialites || ""),
          site_web: p.profil?.site_web || p.site_web || "",
          github: p.profil?.github || p.github || "",
          linkedin: p.profil?.linkedin || p.linkedin || "",
        });
      } catch (e: any) { setError(e.message); }
      setLoading(false);
    }
    fetch();
  }, []);

  const handleSave = async () => {
    setSaving(true);
    try {
      await apiClient.put("/v1/mentor/profil", {
        ...form,
        specialites: form.specialites.split(",").map(s => s.trim()).filter(Boolean),
      });
      const res = await apiClient.get<any>("/v1/mentor/profil");
      setProfil(res.data?.mentor || res.data?.utilisateur || res.data || {});
      setEditing(false);
    } catch (e: any) { setError(e.message); }
    setSaving(false);
  };

  if (loading) return <div className="py-12 text-center"><Loader2 className="h-8 w-8 animate-spin mx-auto" /></div>;

  return (
    <div className="space-y-6 max-w-3xl">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold flex items-center gap-2"><User className="h-6 w-6" /> Mon profil mentor</h1>
        {!editing && <Button variant="outline" onClick={() => setEditing(true)}><Pencil className="h-4 w-4 mr-2" /> Modifier</Button>}
      </div>
      {error && <div className="text-red-600 text-sm bg-red-50 p-3 rounded-lg">{error}</div>}

      {!profil ? <p className="text-muted-foreground">Aucune donnée.</p> : (
        <Card>
          <CardContent className="p-6 space-y-6">
            <div className="flex items-center gap-4">
              <Avatar className="h-20 w-20"><AvatarImage src={profil.avatar || profil.profil?.avatar} /><AvatarFallback className="text-2xl">{profil.prenom?.[0]}{profil.nom?.[0]}</AvatarFallback></Avatar>
              <div>
                <h2 className="text-xl font-bold">{profil.prenom} {profil.nom}</h2>
                <p className="text-sm text-muted-foreground">{profil.email}</p>
                <Badge variant="secondary" className="mt-1">Mentor</Badge>
              </div>
            </div>

            <Separator />

            {editing ? (
              <div className="space-y-4">
                <div><Label>Bio</Label><Textarea value={form.bio} onChange={e => setForm({ ...form, bio: e.target.value })} rows={3} /></div>
                <div><Label>Spécialités (séparées par virgule)</Label><Input value={form.specialites} onChange={e => setForm({ ...form, specialites: e.target.value })} placeholder="React, Python, DevOps..." /></div>
                <div><Label>Site web</Label><Input value={form.site_web} onChange={e => setForm({ ...form, site_web: e.target.value })} /></div>
                <div><Label>GitHub</Label><Input value={form.github} onChange={e => setForm({ ...form, github: e.target.value })} /></div>
                <div><Label>LinkedIn</Label><Input value={form.linkedin} onChange={e => setForm({ ...form, linkedin: e.target.value })} /></div>
                <div className="flex gap-2">
                  <Button onClick={handleSave} disabled={saving}>{saving ? <Loader2 className="h-4 w-4 animate-spin" /> : <><Save className="h-4 w-4 mr-2" /> Enregistrer</>}</Button>
                  <Button variant="outline" onClick={() => setEditing(false)}><X className="h-4 w-4 mr-2" /> Annuler</Button>
                </div>
              </div>
            ) : (
              <div className="space-y-3 text-sm">
                {(profil.profil?.bio || profil.bio) && <div><span className="font-semibold">Bio :</span> {profil.profil?.bio || profil.bio}</div>}
                {(profil.specialites || profil.profil?.specialites) && (
                  <div><span className="font-semibold">Spécialités :</span>
                    <div className="flex flex-wrap gap-1 mt-1">
                      {(Array.isArray(profil.specialites) ? profil.specialites : (profil.profil?.specialites || "").split(",")).filter(Boolean).map((s: string, i: number) => <Badge key={i} variant="outline">{s.trim()}</Badge>)}
                    </div>
                  </div>
                )}
                {(profil.profil?.site_web || profil.site_web) && <div><span className="font-semibold">Site web :</span> <a href={profil.profil?.site_web || profil.site_web} target="_blank" className="text-blue-600 underline">{profil.profil?.site_web || profil.site_web}</a></div>}
                {(profil.profil?.github || profil.github) && <div><span className="font-semibold">GitHub :</span> <a href={profil.profil?.github || profil.github} target="_blank" className="text-blue-600 underline">{profil.profil?.github || profil.github}</a></div>}
                {(profil.profil?.linkedin || profil.linkedin) && <div><span className="font-semibold">LinkedIn :</span> <a href={profil.profil?.linkedin || profil.linkedin} target="_blank" className="text-blue-600 underline">{profil.profil?.linkedin || profil.linkedin}</a></div>}
              </div>
            )}

            {profil.competences && profil.competences.length > 0 && (
              <>
                <Separator />
                <div><h3 className="font-semibold mb-2">Compétences</h3><div className="flex flex-wrap gap-2">{profil.competences.map((c: any) => <Badge key={c.id} variant="secondary">{c.nom}</Badge>)}</div></div>
              </>
            )}
          </CardContent>
        </Card>
      )}
    </div>
  );
}
