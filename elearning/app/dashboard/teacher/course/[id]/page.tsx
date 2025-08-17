import { getServerSession } from "next-auth";
import { authOptions } from "@/app/api/auth/[...nextauth]/route";
import { prisma } from "@/lib/prisma";
import { notFound, redirect } from "next/navigation";

interface Props { params: { id: string } }

export default async function TeacherCoursePage({ params }: Props) {
	const session = await getServerSession(authOptions);
	if (!session?.user) return null;
	if (session.user.role !== "TEACHER") redirect("/dashboard");

	const course = await prisma.course.findFirst({
		where: { id: params.id, teacherId: session.user.id },
		include: { messages: { include: { sender: true }, orderBy: { createdAt: "desc" } }, enrollments: { include: { user: true } } },
	});
	if (!course) notFound();

	async function invite(formData: FormData) {
		"use server";
		const email = String(formData.get("email") || "").trim();
		if (!email) return;
		const user = await prisma.user.findUnique({ where: { email } });
		if (!user) return;
		await prisma.enrollment.upsert({
			where: { userId_courseId: { userId: user.id, courseId: course.id } },
			create: { userId: user.id, courseId: course.id },
			update: {},
		});
	}

	async function postMessage(formData: FormData) {
		"use server";
		const content = String(formData.get("content") || "").trim();
		if (!content) return;
		await prisma.message.create({ data: { content, courseId: course.id, senderId: session.user.id } });
		redirect(`/dashboard/teacher/course/${course.id}`);
	}

	return (
		<div className="p-8 space-y-6">
			<h1 className="text-2xl font-semibold">{course.title}</h1>
			<p className="text-sm text-gray-600">{course.description}</p>

			<section>
				<h2 className="font-semibold mb-2">Mời học sinh</h2>
				<form action={invite} className="flex gap-2">
					<input name="email" className="border p-2 rounded flex-1" placeholder="Email học sinh" />
					<button type="submit" className="bg-black text-white px-4 rounded">Mời</button>
				</form>
			</section>

			<section>
				<h2 className="font-semibold mb-2">Danh sách học sinh</h2>
				<ul className="list-disc pl-6">
					{course.enrollments.map((e) => (
						<li key={e.id}>{e.user.name || e.user.email}</li>
					))}
				</ul>
			</section>

			<section>
				<h2 className="font-semibold mb-2">Bảng tin lớp</h2>
				<form action={postMessage} className="flex gap-2 mb-4">
					<input name="content" className="border p-2 rounded flex-1" placeholder="Thông báo cho lớp..." />
					<button type="submit" className="bg-black text-white px-4 rounded">Gửi</button>
				</form>
				<ul className="space-y-2">
					{course.messages.map((m) => (
						<li key={m.id} className="border p-2 rounded">
							<p className="text-sm">{m.content}</p>
							<p className="text-xs text-gray-500">bởi {m.sender.name || m.sender.email}</p>
						</li>
					))}
				</ul>
			</section>
		</div>
	);
}