import { getServerSession } from "next-auth";
import { authOptions } from "@/app/api/auth/[...nextauth]/route";
import { prisma } from "@/lib/prisma";
import { redirect } from "next/navigation";

export default async function NewCoursePage() {
	const session = await getServerSession(authOptions);
	if (!session?.user) return null;
	if (session.user.role !== "TEACHER") redirect("/dashboard");

	async function create(formData: FormData) {
		"use server";
		const title = String(formData.get("title") || "").trim();
		const description = String(formData.get("description") || "").trim();
		if (!title) return;
		await prisma.course.create({ data: { title, description, teacherId: session.user.id } });
		redirect("/dashboard/teacher");
	}

	return (
		<div className="p-8 space-y-4">
			<h1 className="text-2xl font-semibold">Tạo khóa học</h1>
			<form action={create} className="space-y-4 max-w-lg">
				<input name="title" className="w-full border p-2 rounded" placeholder="Tên khóa học" />
				<textarea name="description" className="w-full border p-2 rounded" placeholder="Mô tả" rows={4} />
				<button type="submit" className="bg-black text-white py-2 px-4 rounded">Tạo</button>
			</form>
		</div>
	);
}