"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";

export default function SignUpPage() {
	const router = useRouter();
	const [name, setName] = useState("");
	const [email, setEmail] = useState("");
	const [password, setPassword] = useState("");
	const [role, setRole] = useState<"STUDENT" | "TEACHER">("STUDENT");
	const [error, setError] = useState<string | null>(null);

	async function onSubmit(e: React.FormEvent) {
		e.preventDefault();
		setError(null);
		const res = await fetch("/api/auth/signup", {
			method: "POST",
			headers: { "Content-Type": "application/json" },
			body: JSON.stringify({ name, email, password, role }),
		});
		if (!res.ok) {
			const data = await res.json().catch(() => ({}));
			setError(data.error || "Có lỗi xảy ra");
			return;
		}
		router.push("/signin");
	}

	return (
		<div className="max-w-md mx-auto py-16">
			<h1 className="text-2xl font-semibold mb-6">Đăng ký</h1>
			<form onSubmit={onSubmit} className="space-y-4">
				<input className="w-full border p-2 rounded" placeholder="Họ tên" value={name} onChange={(e) => setName(e.target.value)} />
				<input className="w-full border p-2 rounded" placeholder="Email" value={email} onChange={(e) => setEmail(e.target.value)} />
				<input className="w-full border p-2 rounded" placeholder="Mật khẩu" type="password" value={password} onChange={(e) => setPassword(e.target.value)} />
				<select className="w-full border p-2 rounded" value={role} onChange={(e) => setRole(e.target.value as any)}>
					<option value="STUDENT">Học sinh</option>
					<option value="TEACHER">Giáo viên</option>
				</select>
				{error && <p className="text-red-600 text-sm">{error}</p>}
				<button type="submit" className="w-full bg-black text-white py-2 rounded">Tạo tài khoản</button>
			</form>
		</div>
	);
}