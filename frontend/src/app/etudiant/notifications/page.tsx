"use client";
import { useEffect, useState, useCallback } from "react";
import { apiClient } from "@/lib/api";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { Loader2, Bell, BellOff, Check, CheckCheck, Trash2, Eye } from "lucide-react";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";

type Notification = {
  id: number; type?: string; titre?: string; message: string;
  lu?: boolean; created_at?: string; data?: any;
};

export default function EtudiantNotificationsPage() {
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [nonLues, setNonLues] = useState<Notification[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [actionLoading, setActionLoading] = useState(false);
  const [tab, setTab] = useState("toutes");

  const fetchAll = useCallback(async () => {
    setLoading(true);
    try {
      const [allRes, unreadRes] = await Promise.all([
        apiClient.get<any>("/v1/etudiant/notifications"),
        apiClient.get<any>("/v1/etudiant/notifications/non-lues").catch(() => ({ data: {} })),
      ]);
      setNotifications(allRes.data?.notifications || allRes.data || []);
      setNonLues(unreadRes.data?.notifications || unreadRes.data || []);
    } catch (e: any) { setError(e.message); }
    setLoading(false);
  }, []);

  useEffect(() => { fetchAll(); }, [fetchAll]);

  const handleMarkRead = async (id: number) => {
    try {
      await apiClient.post(`/v1/etudiant/notifications/${id}/marquer-lu`);
      fetchAll();
    } catch (e: any) { setError(e.message); }
  };

  const handleMarkAllRead = async () => {
    setActionLoading(true);
    try {
      await apiClient.post("/v1/etudiant/notifications/marquer-toutes-lues");
      fetchAll();
    } catch (e: any) { setError(e.message); }
    setActionLoading(false);
  };

  const handleDelete = async (id: number) => {
    try {
      await apiClient.delete(`/v1/etudiant/notifications/${id}`);
      fetchAll();
    } catch (e: any) { setError(e.message); }
  };

  const renderNotification = (n: Notification) => (
    <Card key={n.id} className={`p-4 transition-colors ${!n.lu ? "bg-blue-50/50 border-blue-200" : ""}`}>
      <div className="flex items-start justify-between gap-3">
        <div className="flex items-start gap-3 flex-1">
          <div className={`p-2 rounded-full ${!n.lu ? "bg-blue-100" : "bg-muted"}`}>
            {!n.lu ? <Bell className="h-4 w-4 text-blue-600" /> : <BellOff className="h-4 w-4 text-muted-foreground" />}
          </div>
          <div className="flex-1">
            <div className="flex items-center gap-2">
              <h3 className="text-sm font-semibold">{n.titre || n.type || "Notification"}</h3>
              {!n.lu && <Badge variant="default" className="text-xs px-1.5 py-0">Nouveau</Badge>}
            </div>
            <p className="text-sm text-muted-foreground mt-1">{n.message}</p>
            <p className="text-xs text-muted-foreground mt-1">{n.created_at ? new Date(n.created_at).toLocaleString("fr-FR") : ""}</p>
          </div>
        </div>
        <div className="flex gap-1">
          {!n.lu && (
            <Button size="sm" variant="ghost" onClick={() => handleMarkRead(n.id)} title="Marquer comme lu">
              <Check className="h-3 w-3" />
            </Button>
          )}
          <Button size="sm" variant="ghost" className="text-red-500" onClick={() => handleDelete(n.id)} title="Supprimer">
            <Trash2 className="h-3 w-3" />
          </Button>
        </div>
      </div>
    </Card>
  );

  if (loading) return <div className="py-12 text-center"><Loader2 className="h-8 w-8 animate-spin mx-auto" /></div>;

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div><h1 className="text-2xl font-bold flex items-center gap-2"><Bell className="h-6 w-6" /> Notifications</h1><p className="text-muted-foreground">{nonLues.length} non lue(s)</p></div>
        {nonLues.length > 0 && (
          <Button variant="outline" onClick={handleMarkAllRead} disabled={actionLoading}>
            <CheckCheck className="h-4 w-4 mr-2" /> Tout marquer comme lu
          </Button>
        )}
      </div>
      {error && <div className="text-red-600 text-sm bg-red-50 p-3 rounded-lg">{error}</div>}

      <Tabs value={tab} onValueChange={setTab}>
        <TabsList>
          <TabsTrigger value="toutes">Toutes ({notifications.length})</TabsTrigger>
          <TabsTrigger value="non-lues">Non lues ({nonLues.length})</TabsTrigger>
        </TabsList>

        <TabsContent value="toutes" className="mt-4 space-y-3">
          {notifications.length === 0 ? <Card><CardContent className="py-12 text-center text-muted-foreground">Aucune notification.</CardContent></Card> : notifications.map(renderNotification)}
        </TabsContent>

        <TabsContent value="non-lues" className="mt-4 space-y-3">
          {nonLues.length === 0 ? <Card><CardContent className="py-12 text-center text-muted-foreground">Toutes les notifications ont été lues.</CardContent></Card> : nonLues.map(renderNotification)}
        </TabsContent>
      </Tabs>
    </div>
  );
}
