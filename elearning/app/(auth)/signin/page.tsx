"use client";

import { signIn } from "next-auth/react";
import { useState } from "react";
import Link from "next/link";

export default function SignInPage() {
	const [email, setEmail] = useState("");
	const [password, setPassword] = useState("");
	const [error, setError] = useState<string | null>(null);

	async function onSubmit(e: React.FormEvent) {
		e.preventDefault();
		setError(null);
		const res = await signIn("credentials", {
			redirect: true,
			email,
			password,
			callbackUrl: "/dashboard",
		});
		if (res?.error) setError(res.error);
	}

	return (
		<div className="max-w-md mx-auto py-16">
			<h1 className="text-2xl font-semibold mb-6">Đăng nhập</h1>
			<form onSubmit={onSubmit} className="space-y-4">
				<input className="w-full border p-2 rounded" placeholder="Email" value={email} onChange={(e) => setEmail(e.target.value)} />
				<input className="w-full border p-2 rounded" placeholder="Mật khẩu" type="password" value={password} onChange={(e) => setPassword(e.target.value)} />
				{error && <p className="text-red-600 text-sm">{error}</p>}
				<button type="submit" className="w-full bg-black text-white py-2 rounded">Đăng nhập</button>
			</form>
			<p className="text-sm mt-4">Chưa có tài khoản? <Link href="/signup" className="underline">Đăng ký</Link></p>
		</div>
	);
}