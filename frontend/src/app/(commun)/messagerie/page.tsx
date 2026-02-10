"use client";
import { useEffect, useState, useCallback, useRef } from "react";
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
import { Loader2, Plus, Mail, Send, Check, User } from "lucide-react";

export default function MessageriePage() {
  const [conversations, setConversations] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Active conversation
  const [activeConv, setActiveConv] = useState<any>(null);
  const [messages, setMessages] = useState<any[]>([]);
  const [loadingMessages, setLoadingMessages] = useState(false);
  const [newMessage, setNewMessage] = useState("");
  const [sendingMsg, setSendingMsg] = useState(false);
  const messagesEndRef = useRef<HTMLDivElement>(null);

  // Create conversation
  const [showCreate, setShowCreate] = useState(false);
  const [createForm, setCreateForm] = useState({ destinataire_id: "", sujet: "", message: "" });
  const [createLoading, setCreateLoading] = useState(false);

  const fetchConversations = useCallback(async () => {
    setLoading(true);
    try {
      const res = await apiClient.get<any>("/v1/commun/messagerie/conversations");
      setConversations(res.data?.conversations || res.data || []);
    } catch (e: any) { setError(e.message); }
    setLoading(false);
  }, []);

  useEffect(() => { fetchConversations(); }, [fetchConversations]);

  const openConversation = async (conv: any) => {
    setActiveConv(conv);
    setLoadingMessages(true);
    try {
      const res = await apiClient.get<any>(`/v1/commun/messagerie/conversations/${conv.id}/messages`);
      setMessages(res.data?.messages || res.data || []);
      setTimeout(() => messagesEndRef.current?.scrollIntoView({ behavior: "smooth" }), 100);
    } catch (e: any) { setError(e.message); }
    setLoadingMessages(false);
  };

  const handleSend = async () => {
    if (!activeConv || !newMessage.trim()) return;
    setSendingMsg(true);
    try {
      await apiClient.post(`/v1/commun/messagerie/conversations/${activeConv.id}/messages`, { contenu: newMessage });
      setNewMessage("");
      openConversation(activeConv);
    } catch (e: any) { setError(e.message); }
    setSendingMsg(false);
  };

  const handleMarkRead = async (convId: number, msgId: number) => {
    try { await apiClient.post(`/v1/commun/messagerie/conversations/${convId}/messages/${msgId}/lire`); } catch {}
  };

  const handleCreate = async () => {
    setCreateLoading(true);
    try {
      await apiClient.post("/v1/commun/messagerie/conversations", {
        destinataire_id: parseInt(createForm.destinataire_id),
        sujet: createForm.sujet,
        message: createForm.message,
      });
      setShowCreate(false);
      setCreateForm({ destinataire_id: "", sujet: "", message: "" });
      fetchConversations();
    } catch (e: any) { setError(e.message); }
    setCreateLoading(false);
  };

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === "Enter" && !e.shiftKey) { e.preventDefault(); handleSend(); }
  };

  if (loading) return <div className="py-12 text-center"><Loader2 className="h-8 w-8 animate-spin mx-auto" /></div>;

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold flex items-center gap-2"><Mail className="h-6 w-6" /> Messagerie</h1>
        <Button onClick={() => setShowCreate(true)}><Plus className="h-4 w-4 mr-2" /> Nouvelle conversation</Button>
      </div>
      {error && <div className="text-red-600 text-sm bg-red-50 p-3 rounded-lg">{error}</div>}

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 h-[70vh]">
        {/* Conversation List */}
        <Card className="md:col-span-1 overflow-y-auto">
          <CardHeader className="pb-2"><CardTitle className="text-sm">Conversations ({conversations.length})</CardTitle></CardHeader>
          <CardContent className="p-2 space-y-1">
            {conversations.length === 0 ? <p className="text-muted-foreground text-center py-8 text-sm">Aucune conversation.</p> : (
              conversations.map((c: any) => (
                <div
                  key={c.id}
                  className={`flex items-center gap-3 p-3 rounded-lg cursor-pointer hover:bg-accent transition ${activeConv?.id === c.id ? "bg-accent" : ""}`}
                  onClick={() => openConversation(c)}
                >
                  <Avatar className="h-9 w-9">
                    <AvatarImage src={c.participant?.avatar || c.destinataire?.avatar} />
                    <AvatarFallback className="text-xs">{(c.participant?.prenom || c.destinataire?.prenom || c.sujet || "?")?.[0]}</AvatarFallback>
                  </Avatar>
                  <div className="flex-1 min-w-0">
                    <p className="font-semibold text-sm truncate">{c.participant?.prenom || c.destinataire?.prenom} {c.participant?.nom || c.destinataire?.nom || c.sujet}</p>
                    <p className="text-xs text-muted-foreground truncate">{c.dernier_message?.contenu || c.sujet || ""}</p>
                  </div>
                  {(c.non_lus || c.messages_non_lus) > 0 && <Badge className="text-xs">{c.non_lus || c.messages_non_lus}</Badge>}
                </div>
              ))
            )}
          </CardContent>
        </Card>

        {/* Message Area */}
        <Card className="md:col-span-2 flex flex-col">
          {!activeConv ? (
            <div className="flex-1 flex items-center justify-center text-muted-foreground"><p>Sélectionnez une conversation</p></div>
          ) : (
            <>
              <CardHeader className="pb-2 border-b">
                <div className="flex items-center gap-2">
                  <Avatar className="h-8 w-8"><AvatarFallback className="text-xs">{(activeConv.participant?.prenom || activeConv.destinataire?.prenom || "?")?.[0]}</AvatarFallback></Avatar>
                  <div>
                    <CardTitle className="text-sm">{activeConv.participant?.prenom || activeConv.destinataire?.prenom} {activeConv.participant?.nom || activeConv.destinataire?.nom}</CardTitle>
                    {activeConv.sujet && <p className="text-xs text-muted-foreground">{activeConv.sujet}</p>}
                  </div>
                </div>
              </CardHeader>
              <CardContent className="flex-1 overflow-y-auto p-4 space-y-3">
                {loadingMessages ? <Loader2 className="h-6 w-6 animate-spin mx-auto mt-8" /> : (
                  messages.map((m: any) => {
                    const isMe = m.est_expediteur || m.expediteur_id === undefined;
                    return (
                      <div key={m.id} className={`flex ${isMe ? "justify-end" : "justify-start"}`}>
                        <div className={`max-w-[70%] rounded-lg p-3 text-sm ${isMe ? "bg-primary text-primary-foreground" : "bg-muted"}`}>
                          <p className="whitespace-pre-wrap">{m.contenu}</p>
                          <p className={`text-xs mt-1 ${isMe ? "text-primary-foreground/70" : "text-muted-foreground"}`}>{m.created_at ? new Date(m.created_at).toLocaleTimeString("fr-FR", { hour: "2-digit", minute: "2-digit" }) : ""}</p>
                        </div>
                      </div>
                    );
                  })
                )}
                <div ref={messagesEndRef} />
              </CardContent>
              <div className="p-3 border-t flex gap-2">
                <Input value={newMessage} onChange={e => setNewMessage(e.target.value)} onKeyDown={handleKeyDown} placeholder="Écrire un message..." className="flex-1" />
                <Button onClick={handleSend} disabled={sendingMsg || !newMessage.trim()}>{sendingMsg ? <Loader2 className="h-4 w-4 animate-spin" /> : <Send className="h-4 w-4" />}</Button>
              </div>
            </>
          )}
        </Card>
      </div>

      {/* Create Conversation Dialog */}
      <Dialog open={showCreate} onOpenChange={setShowCreate}>
        <DialogContent><DialogHeader><DialogTitle>Nouvelle conversation</DialogTitle><DialogDescription>Démarrer une nouvelle conversation</DialogDescription></DialogHeader>
          <div className="space-y-4">
            <div><Label>ID du destinataire</Label><Input value={createForm.destinataire_id} onChange={e => setCreateForm({ ...createForm, destinataire_id: e.target.value })} placeholder="ID de l'utilisateur" /></div>
            <div><Label>Sujet (optionnel)</Label><Input value={createForm.sujet} onChange={e => setCreateForm({ ...createForm, sujet: e.target.value })} /></div>
            <div><Label>Message</Label><Textarea value={createForm.message} onChange={e => setCreateForm({ ...createForm, message: e.target.value })} rows={3} placeholder="Votre premier message..." /></div>
          </div>
          <DialogFooter><Button variant="outline" onClick={() => setShowCreate(false)}>Annuler</Button><Button onClick={handleCreate} disabled={createLoading || !createForm.destinataire_id || !createForm.message}>{createLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : "Envoyer"}</Button></DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
