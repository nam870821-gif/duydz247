import { prisma } from "@/lib/prisma";
import { NextResponse } from "next/server";
import { hash } from "bcryptjs";
import { z } from "zod";

const schema = z.object({
	name: z.string().min(1),
	email: z.string().email(),
	password: z.string().min(6),
	role: z.enum(["STUDENT", "TEACHER"]),
});

export async function POST(req: Request) {
	try {
		const body = await req.json();
		const parsed = schema.safeParse(body);
		if (!parsed.success) {
			return NextResponse.json({ error: "Dữ liệu không hợp lệ" }, { status: 400 });
		}
		const { name, email, password, role } = parsed.data;
		const existing = await prisma.user.findUnique({ where: { email } });
		if (existing) {
			return NextResponse.json({ error: "Email đã tồn tại" }, { status: 400 });
		}
		const passwordHash = await hash(password, 10);
		await prisma.user.create({ data: { name, email, passwordHash, role } });
		return NextResponse.json({ ok: true });
	} catch (e) {
		return NextResponse.json({ error: "Lỗi máy chủ" }, { status: 500 });
	}
}