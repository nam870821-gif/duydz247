import { getServerSession } from "next-auth";
import { authOptions } from "@/app/api/auth/[...nextauth]/route";
import Link from "next/link";

export default async function DashboardPage() {
	const session = await getServerSession(authOptions);
	if (!session?.user) {
		return (
			<div className="p-8">
				<p>Vui lòng <Link href="/signin" className="underline">đăng nhập</Link>.</p>
			</div>
		);
	}

	return (
		<div className="p-8 space-y-4">
			<h1 className="text-2xl font-semibold">Bảng điều khiển</h1>
			<p>Xin chào, {session.user.name || session.user.email}</p>
			<div className="space-x-4">
				<Link href="/dashboard/student" className="underline">Dành cho Học sinh</Link>
				<Link href="/dashboard/teacher" className="underline">Dành cho Giáo viên</Link>
			</div>
		</div>
	);
}