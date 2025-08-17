import { getServerSession } from "next-auth";
import { authOptions } from "@/app/api/auth/[...nextauth]/route";
import { prisma } from "@/lib/prisma";
import Link from "next/link";
import { redirect } from "next/navigation";

export default async function TeacherDashboard() {
	const session = await getServerSession(authOptions);
	if (!session?.user) return null;
	if (session.user.role !== "TEACHER") redirect("/dashboard");

	const courses = await prisma.course.findMany({
		where: { teacherId: session.user.id },
		orderBy: { createdAt: "desc" },
	});

	return (
		<div className="p-8 space-y-4">
			<h1 className="text-2xl font-semibold">Giáo viên</h1>
			<Link href="/dashboard/teacher/new" className="underline">+ Tạo khóa học</Link>
			{courses.length === 0 ? (
				<p>Chưa có khóa học.</p>
			) : (
				<ul className="list-disc pl-6">
					{courses.map((c) => (
						<li key={c.id}><Link className="underline" href={`/dashboard/teacher/course/${c.id}`}>{c.title}</Link></li>
					))}
				</ul>
			)}
		</div>
	);
}