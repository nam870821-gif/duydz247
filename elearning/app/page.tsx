import Link from "next/link";

export default function Home() {
	return (
		<div className="min-h-screen p-8">
			<h1 className="text-3xl font-bold mb-4">E-Learning</h1>
			<p className="mb-6 text-gray-600">Nền tảng học tập trực tuyến cho giáo viên và học sinh.</p>
			<div className="space-x-4">
				<Link href="/signin" className="underline">Đăng nhập</Link>
				<Link href="/signup" className="underline">Đăng ký</Link>
				<Link href="/dashboard" className="underline">Bảng điều khiển</Link>
			</div>
		</div>
	);
}
