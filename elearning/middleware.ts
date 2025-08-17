import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";
import { getToken } from "next-auth/jwt";

const AUTH_PAGES = ["/signin", "/signup"]; 

export async function middleware(req: NextRequest) {
	const { pathname } = req.nextUrl;

	if (AUTH_PAGES.some((p) => pathname.startsWith(p))) {
		return NextResponse.next();
	}

	// Protect dashboard and app routes
	if (pathname.startsWith("/dashboard")) {
		const token = await getToken({ req, secret: process.env.NEXTAUTH_SECRET });
		if (!token) {
			const signinUrl = new URL("/signin", req.url);
			signinUrl.searchParams.set("callbackUrl", req.url);
			return NextResponse.redirect(signinUrl);
		}

		// Role protection examples
		if (pathname.startsWith("/dashboard/teacher") && token.role !== "TEACHER") {
			return NextResponse.redirect(new URL("/dashboard", req.url));
		}
		if (pathname.startsWith("/dashboard/student") && token.role !== "STUDENT") {
			return NextResponse.redirect(new URL("/dashboard", req.url));
		}
	}

	return NextResponse.next();
}

export const config = {
	matcher: ["/dashboard/:path*", "/signin", "/signup"],
};