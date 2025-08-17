import { getServerSession } from "next-auth";
import { authOptions } from "@/app/api/auth/[...nextauth]/route";
import { prisma } from "@/lib/prisma";
import { notFound } from "next/navigation";

interface Props { params: { id: string } }

export default async function StudentCoursePage({ params }: Props) {
	const session = await getServerSession(authOptions);
	if (!session?.user) return null;

	const enrollment = await prisma.enrollment.findFirst({
		where: { userId: session.user.id, courseId: params.id },
		include: { course: { include: { messages: { include: { sender: true }, orderBy: { createdAt: "desc" } } } } },
	});
	if (!enrollment) notFound();

	const course = enrollment.course;

	return (
		<div className="p-8 space-y-6">
			<h1 className="text-2xl font-semibold">{course.title}</h1>
			<p className="text-sm text-gray-600">{course.description}</p>
			<section>
				<h2 className="font-semibold mb-2">Bảng tin lớp</h2>
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