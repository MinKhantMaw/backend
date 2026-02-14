import { useEffect, useMemo, useState } from "react";
import axios from "axios";
import { Button } from "./ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "./ui/card";
import { Checkbox } from "./ui/checkbox";
import { Input } from "./ui/input";
import { Label } from "./ui/label";

function UserCreatePage() {
  const [token, setToken] = useState(() => localStorage.getItem("api_token") ?? "");
  const [currentUser, setCurrentUser] = useState(null);
  const [currentPermissions, setCurrentPermissions] = useState([]);
  const [roles, setRoles] = useState([]);
  const [selectedRoles, setSelectedRoles] = useState([]);
  const [form, setForm] = useState({
    name: "",
    email: "",
    password: "",
  });
  const [loadingRoles, setLoadingRoles] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [validationErrors, setValidationErrors] = useState({});

  const authHeaders = useMemo(
    () => ({
      Authorization: `Bearer ${token}`,
      Accept: "application/json",
    }),
    [token],
  );

  useEffect(() => {
    localStorage.setItem("api_token", token);
  }, [token]);

  const can = (permission) => currentPermissions.includes(permission);

  const fetchCurrentUser = async () => {
    if (!token) {
      setCurrentUser(null);
      setCurrentPermissions([]);
      return;
    }

    try {
      const response = await axios.get("/api/v1/auth/me", {
        headers: authHeaders,
      });
      const user = response?.data?.data ?? null;
      setCurrentUser(user);
      setCurrentPermissions(Array.isArray(user?.permissions) ? user.permissions : []);
    } catch {
      setCurrentUser(null);
      setCurrentPermissions([]);
    }
  };

  const fetchRoles = async () => {
    if (!token) {
      setError("Add a Bearer token first.");
      return;
    }

    if (!can("roles.view")) {
      setError("You do not have permission to view roles.");
      return;
    }

    setLoadingRoles(true);
    setError("");
    setMessage("");

    try {
      const response = await axios.get("/api/v1/roles?per_page=100", {
        headers: authHeaders,
      });
      const roleData = Array.isArray(response?.data?.data) ? response.data.data : [];
      setRoles(roleData);
    } catch (err) {
      const apiMessage = err?.response?.data?.message ?? "Failed to load roles.";
      setError(apiMessage);
    } finally {
      setLoadingRoles(false);
    }
  };

  const toggleRole = (roleName, checked) => {
    setSelectedRoles((prev) => {
      if (checked) return [...new Set([...prev, roleName])];
      return prev.filter((name) => name !== roleName);
    });
  };

  const submit = async (event) => {
    event.preventDefault();
    if (!can("users.create")) {
      setError("You do not have permission to create users.");
      return;
    }

    setSubmitting(true);
    setError("");
    setMessage("");
    setValidationErrors({});

    try {
      const payload = {
        ...form,
        roles: selectedRoles,
      };

      const response = await axios.post("/api/v1/users", payload, {
        headers: authHeaders,
      });

      setMessage(response?.data?.message ?? "User created successfully.");
      setForm({ name: "", email: "", password: "" });
      setSelectedRoles([]);
    } catch (err) {
      const data = err?.response?.data;
      setError(data?.message ?? "User creation failed.");
      setValidationErrors(data?.errors ?? {});
    } finally {
      setSubmitting(false);
    }
  };

  useEffect(() => {
    fetchCurrentUser();
  }, [token]);

  return (
    <div className="min-h-screen bg-gradient-to-b from-slate-100 via-cyan-50 to-slate-50 p-4 sm:p-8">
      <div className="mx-auto grid max-w-5xl gap-6 lg:grid-cols-[1fr_1.4fr]">
        <Card>
          <CardHeader>
            <CardTitle>API Access</CardTitle>
            <CardDescription>Use a token from a user that can view roles and create users.</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="token">Bearer Token</Label>
              <Input
                id="token"
                value={token}
                onChange={(e) => setToken(e.target.value)}
                placeholder="eyJ0eXAiOiJKV1QiLCJh..."
              />
            </div>
            <Button onClick={fetchRoles} disabled={loadingRoles || !can("roles.view")} className="w-full">
              {loadingRoles ? "Loading Roles..." : "Load Roles"}
            </Button>
            {currentUser && (
              <div className="rounded-md border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                <p className="font-semibold text-slate-700">Signed in as {currentUser.email}</p>
                <p>`users.create`: {can("users.create") ? "yes" : "no"}</p>
                <p>`roles.view`: {can("roles.view") ? "yes" : "no"}</p>
              </div>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Create User With Roles</CardTitle>
            <CardDescription>Name, email, password, and one or more roles are required.</CardDescription>
          </CardHeader>
          <CardContent>
            <form className="space-y-5" onSubmit={submit}>
              <div className="space-y-2">
                <Label htmlFor="name">Name</Label>
                <Input
                  id="name"
                  value={form.name}
                  onChange={(e) => setForm((prev) => ({ ...prev, name: e.target.value }))}
                  placeholder="John Doe"
                  required
                />
                {validationErrors.name && <p className="text-sm text-red-600">{validationErrors.name[0]}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="email">Email</Label>
                <Input
                  id="email"
                  type="email"
                  value={form.email}
                  onChange={(e) => setForm((prev) => ({ ...prev, email: e.target.value }))}
                  placeholder="john@example.com"
                  required
                />
                {validationErrors.email && <p className="text-sm text-red-600">{validationErrors.email[0]}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="password">Password</Label>
                <Input
                  id="password"
                  type="password"
                  value={form.password}
                  onChange={(e) => setForm((prev) => ({ ...prev, password: e.target.value }))}
                  placeholder="secret123"
                  minLength={8}
                  required
                />
                {validationErrors.password && (
                  <p className="text-sm text-red-600">{validationErrors.password[0]}</p>
                )}
              </div>

              <div className="space-y-2">
                <Label>Roles</Label>
                <div className="grid gap-3 rounded-md border border-slate-200 p-4 sm:grid-cols-2">
                  {roles.length === 0 && (
                    <p className="text-sm text-slate-500">No roles loaded yet. Click "Load Roles" first.</p>
                  )}
                  {roles.map((role) => (
                    <label key={role.id} className="flex items-center gap-2 text-sm text-slate-700">
                      <Checkbox
                        checked={selectedRoles.includes(role.name)}
                        onChange={(e) => toggleRole(role.name, e.target.checked)}
                      />
                      <span>{role.name}</span>
                    </label>
                  ))}
                </div>
                {validationErrors.roles && <p className="text-sm text-red-600">{validationErrors.roles[0]}</p>}
              </div>

              <Button type="submit" disabled={submitting || !can("users.create")} className="w-full">
                {submitting ? "Creating..." : "Create User"}
              </Button>

              {message && (
                <div className="rounded-md border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                  {message}
                </div>
              )}
              {error && (
                <div className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700">
                  {error}
                </div>
              )}
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}

export default UserCreatePage;
