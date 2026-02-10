"use client";
import { useEffect, useState, useCallback } from "react";
import { apiClient } from "@/lib/api";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Separator } from "@/components/ui/separator";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from "@/components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Loader2, Plus, MessageSquare, Heart, Flag, Eye, BookmarkPlus, BookmarkMinus, Send, Pencil, Trash2 } from "lucide-react";

export default function ForumPage() {
  const [categories, setCategories] = useState<any[]>([]);
  const [discussions, setDiscussions] = useState<any[]>([]);
  const [mesDiscussions, setMesDiscussions] = useState<any[]>([]);
  const [suivies, setSuivies] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [tab, setTab] = useState("toutes");

  // Discussion detail
  const [selectedDiscussion, setSelectedDiscussion] = useState<any>(null);
  const [messages, setMessages] = useState<any[]>([]);
  const [loadingMessages, setLoadingMessages] = useState(false);
  const [newMessage, setNewMessage] = useState("");
  const [sendingMsg, setSendingMsg] = useState(false);

  // Create discussion form
  const [showCreate, setShowCreate] = useState(false);
  const [createForm, setCreateForm] = useState({ titre: "", contenu: "", categorie_forum_id: "" });
  const [createLoading, setCreateLoading] = useState(false);

  const fetchAll = useCallback(async () => {
    setLoading(true);
    try {
      const [catRes, discRes, mesRes, suivRes] = await Promise.all([
        apiClient.get<any>("/v1/commun/forum/categories"),
        apiClient.get<any>("/v1/commun/forum/discussions"),
        apiClient.get<any>("/v1/commun/forum/mes-discussions").catch(() => ({ data: {} })),
        apiClient.get<any>("/v1/commun/forum/discussions-suivies").catch(() => ({ data: {} })),
      ]);
      setCategories(catRes.data?.categories || catRes.data || []);
      setDiscussions(discRes.data?.discussions || discRes.data || []);
      setMesDiscussions(mesRes.data?.discussions || mesRes.data || []);
      setSuivies(suivRes.data?.discussions || suivRes.data || []);
    } catch (e: any) { setError(e.message); }
    setLoading(false);
  }, []);

  useEffect(() => { fetchAll(); }, [fetchAll]);

  const openDiscussion = async (disc: any) => {
    setSelectedDiscussion(disc);
    setLoadingMessages(true);
    try {
      const res = await apiClient.get<any>(`/v1/commun/forum/discussions/${disc.id}/messages`);
      setMessages(res.data?.messages || res.data || []);
    } catch (e: any) { setError(e.message); }
    setLoadingMessages(false);
  };

  const handleSendMessage = async () => {
    if (!selectedDiscussion || !newMessage.trim()) return;
    setSendingMsg(true);
    try {
      await apiClient.post(`/v1/commun/forum/discussions/${selectedDiscussion.id}/messages`, { contenu: newMessage });
      setNewMessage("");
      openDiscussion(selectedDiscussion);
    } catch (e: any) { setError(e.message); }
    setSendingMsg(false);
  };

  const handleLike = async (discussionId: number, messageId: number) => {
    try { await apiClient.post(`/v1/commun/forum/discussions/${discussionId}/messages/${messageId}/aimer`); openDiscussion(selectedDiscussion); } catch (e: any) { setError(e.message); }
  };

  const handleSignaler = async (discussionId: number, messageId: number) => {
    try { await apiClient.post(`/v1/commun/forum/discussions/${discussionId}/messages/${messageId}/signaler`, { raison: "Contenu inapproprié" }); } catch (e: any) { setError(e.message); }
  };

  const handleCreate = async () => {
    setCreateLoading(true);
    try {
      await apiClient.post("/v1/commun/forum/discussions", {
        ...createForm,
        categorie_forum_id: createForm.categorie_forum_id ? parseInt(createForm.categorie_forum_id) : undefined,
      });
      setShowCreate(false);
      setCreateForm({ titre: "", contenu: "", categorie_forum_id: "" });
      fetchAll();
    } catch (e: any) { setError(e.message); }
    setCreateLoading(false);
  };

  const handleSuivre = async (id: number) => {
    try { await apiClient.post(`/v1/commun/forum/discussions/${id}/suivre`); fetchAll(); } catch (e: any) { setError(e.message); }
  };

  const handleNePlusSuivre = async (id: number) => {
    try { await apiClient.delete(`/v1/commun/forum/discussions/${id}/suivre`); fetchAll(); } catch (e: any) { setError(e.message); }
  };

  const handleDeleteDiscussion = async (id: number) => {
    if (!confirm("Supprimer cette discussion ?")) return;
    try { await apiClient.delete(`/v1/commun/forum/discussions/${id}`); setSelectedDiscussion(null); fetchAll(); } catch (e: any) { setError(e.message); }
  };

  const handleDeleteMessage = async (discussionId: number, messageId: number) => {
    try { await apiClient.delete(`/v1/commun/forum/discussions/${discussionId}/messages/${messageId}`); openDiscussion(selectedDiscussion); } catch (e: any) { setError(e.message); }
  };

  const renderDiscussionList = (list: any[]) => (
    list.length === 0 ? <p className="text-muted-foreground text-center py-8">Aucune discussion.</p> : (
      <div className="space-y-3">
        {list.map((d: any) => (
          <Card key={d.id} className="cursor-pointer hover:bg-accent/50 transition" onClick={() => openDiscussion(d)}>
            <CardContent className="py-3 px-4">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <h3 className="font-semibold">{d.titre}</h3>
                  <p className="text-sm text-muted-foreground line-clamp-1 mt-1">{d.contenu}</p>
                  <div className="flex items-center gap-3 mt-2 text-xs text-muted-foreground">
                    <span>{d.auteur?.prenom || d.utilisateur?.prenom} {d.auteur?.nom || d.utilisateur?.nom}</span>
                    {d.categorie && <Badge variant="outline" className="text-xs">{d.categorie.nom || d.categorie}</Badge>}
                    <span>💬 {d.nombre_messages || d.messages_count || 0}</span>
                    <span>{d.created_at ? new Date(d.created_at).toLocaleDateString("fr-FR") : ""}</span>
                  </div>
                </div>
                <div className="flex gap-1 ml-2" onClick={e => e.stopPropagation()}>
                  {suivies.some((s: any) => s.id === d.id)
                    ? <Button size="sm" variant="ghost" onClick={() => handleNePlusSuivre(d.id)}><BookmarkMinus className="h-3 w-3" /></Button>
                    : <Button size="sm" variant="ghost" onClick={() => handleSuivre(d.id)}><BookmarkPlus className="h-3 w-3" /></Button>}
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    )
  );

  if (loading) return <div className="py-12 text-center"><Loader2 className="h-8 w-8 animate-spin mx-auto" /></div>;

  // Discussion detail view
  if (selectedDiscussion) {
    return (
      <div className="space-y-6 max-w-3xl mx-auto">
        <Button variant="ghost" onClick={() => setSelectedDiscussion(null)}>← Retour aux discussions</Button>
        <Card>
          <CardHeader>
            <div className="flex items-start justify-between">
              <div>
                <CardTitle>{selectedDiscussion.titre}</CardTitle>
                <p className="text-sm text-muted-foreground mt-1">
                  Par {selectedDiscussion.auteur?.prenom || selectedDiscussion.utilisateur?.prenom} {selectedDiscussion.auteur?.nom || selectedDiscussion.utilisateur?.nom} · {selectedDiscussion.created_at ? new Date(selectedDiscussion.created_at).toLocaleDateString("fr-FR") : ""}
                </p>
              </div>
              <Button size="sm" variant="ghost" className="text-red-500" onClick={() => handleDeleteDiscussion(selectedDiscussion.id)}><Trash2 className="h-4 w-4" /></Button>
            </div>
          </CardHeader>
          <CardContent>
            <p className="whitespace-pre-wrap">{selectedDiscussion.contenu}</p>
          </CardContent>
        </Card>

        <Separator />

        <h3 className="font-semibold flex items-center gap-2"><MessageSquare className="h-4 w-4" /> Messages ({messages.length})</h3>

        {loadingMessages ? <Loader2 className="h-6 w-6 animate-spin mx-auto" /> : (
          <div className="space-y-3">
            {messages.map((m: any) => (
              <Card key={m.id}>
                <CardContent className="py-3 px-4">
                  <div className="flex items-start gap-3">
                    <Avatar className="h-8 w-8"><AvatarImage src={m.auteur?.avatar} /><AvatarFallback className="text-xs">{m.auteur?.prenom?.[0]}{m.auteur?.nom?.[0]}</AvatarFallback></Avatar>
                    <div className="flex-1">
                      <div className="flex items-center gap-2"><span className="font-semibold text-sm">{m.auteur?.prenom || m.utilisateur?.prenom} {m.auteur?.nom || m.utilisateur?.nom}</span><span className="text-xs text-muted-foreground">{m.created_at ? new Date(m.created_at).toLocaleDateString("fr-FR") : ""}</span></div>
                      <p className="text-sm mt-1 whitespace-pre-wrap">{m.contenu}</p>
                      <div className="flex gap-2 mt-2">
                        <Button size="sm" variant="ghost" onClick={() => handleLike(selectedDiscussion.id, m.id)}><Heart className="h-3 w-3 mr-1" />{m.nombre_likes || m.likes_count || 0}</Button>
                        <Button size="sm" variant="ghost" onClick={() => handleSignaler(selectedDiscussion.id, m.id)}><Flag className="h-3 w-3" /></Button>
                        <Button size="sm" variant="ghost" className="text-red-500" onClick={() => handleDeleteMessage(selectedDiscussion.id, m.id)}><Trash2 className="h-3 w-3" /></Button>
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        )}

        <div className="flex gap-2">
          <Textarea value={newMessage} onChange={e => setNewMessage(e.target.value)} placeholder="Écrire un message..." rows={2} className="flex-1" />
          <Button onClick={handleSendMessage} disabled={sendingMsg || !newMessage.trim()}>{sendingMsg ? <Loader2 className="h-4 w-4 animate-spin" /> : <Send className="h-4 w-4" />}</Button>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6 max-w-3xl mx-auto">
      <div className="flex items-center justify-between">
        <div><h1 className="text-2xl font-bold flex items-center gap-2"><MessageSquare className="h-6 w-6" /> Forum</h1><p className="text-muted-foreground">{discussions.length} discussions</p></div>
        <Button onClick={() => setShowCreate(true)}><Plus className="h-4 w-4 mr-2" /> Nouvelle discussion</Button>
      </div>
      {error && <div className="text-red-600 text-sm bg-red-50 p-3 rounded-lg">{error}</div>}

      {/* Categories */}
      {categories.length > 0 && (
        <div className="flex flex-wrap gap-2">
          {categories.map((c: any) => <Badge key={c.id} variant="secondary" className="cursor-pointer">{c.nom}</Badge>)}
        </div>
      )}

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList><TabsTrigger value="toutes">Toutes ({discussions.length})</TabsTrigger><TabsTrigger value="mes">Mes discussions ({mesDiscussions.length})</TabsTrigger><TabsTrigger value="suivies">Suivies ({suivies.length})</TabsTrigger></TabsList>
        <TabsContent value="toutes" className="mt-4">{renderDiscussionList(discussions)}</TabsContent>
        <TabsContent value="mes" className="mt-4">{renderDiscussionList(mesDiscussions)}</TabsContent>
        <TabsContent value="suivies" className="mt-4">{renderDiscussionList(suivies)}</TabsContent>
      </Tabs>

      {/* Create Dialog */}
      <Dialog open={showCreate} onOpenChange={setShowCreate}>
        <DialogContent><DialogHeader><DialogTitle>Nouvelle discussion</DialogTitle><DialogDescription>Créez une discussion sur le forum</DialogDescription></DialogHeader>
          <div className="space-y-4">
            <div><Label>Titre</Label><Input value={createForm.titre} onChange={e => setCreateForm({ ...createForm, titre: e.target.value })} /></div>
            <div><Label>Contenu</Label><Textarea value={createForm.contenu} onChange={e => setCreateForm({ ...createForm, contenu: e.target.value })} rows={4} /></div>
            {categories.length > 0 && (
              <div><Label>Catégorie</Label>
                <Select value={createForm.categorie_forum_id} onValueChange={v => setCreateForm({ ...createForm, categorie_forum_id: v })}>
                  <SelectTrigger><SelectValue placeholder="Choisir une catégorie" /></SelectTrigger>
                  <SelectContent>{categories.map((c: any) => <SelectItem key={c.id} value={String(c.id)}>{c.nom}</SelectItem>)}</SelectContent>
                </Select>
              </div>
            )}
          </div>
          <DialogFooter><Button variant="outline" onClick={() => setShowCreate(false)}>Annuler</Button><Button onClick={handleCreate} disabled={createLoading || !createForm.titre || !createForm.contenu}>{createLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : "Publier"}</Button></DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
