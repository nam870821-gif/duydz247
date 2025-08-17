import { getServerSession } from "next-auth";
import { authOptions } from "@/app/api/auth/[...nextauth]/route";
import { prisma } from "@/lib/prisma";
import Link from "next/link";

export default async function StudentDashboard() {
	const session = await getServerSession(authOptions);
	if (!session?.user) return null;

	const enrollments = await prisma.enrollment.findMany({
		where: { userId: session.user.id },
		include: { course: true },
	});

	return (
		<div className="p-8 space-y-4">
			<h1 className="text-2xl font-semibold">Học sinh</h1>
			{enrollments.length === 0 ? (
				<p>Chưa có khóa học. Hãy liên hệ giáo viên để được mời vào lớp.</p>
			) : (
				<ul className="list-disc pl-6">
					{enrollments.map((e) => (
						<li key={e.id}>
							<Link className="underline" href={`/dashboard/student/course/${e.courseId}`}>{e.course.title}</Link>
						</li>
					))}
				</ul>
			)}
		</div>
	);
}