import NextAuth from "next-auth";
import Credentials from "next-auth/providers/credentials";
import { PrismaAdapter } from "@auth/prisma-adapter";
import { prisma } from "@/lib/prisma";
import { compare } from "bcryptjs";
import { z } from "zod";

const credentialsSchema = z.object({
	email: z.string().email(),
	password: z.string().min(6),
});

export const authOptions = {
	adapter: PrismaAdapter(prisma),
	providers: [
		Credentials({
			name: "Credentials",
			credentials: {
				email: { label: "Email", type: "email" },
				password: { label: "Password", type: "password" },
			},
			async authorize(raw) {
				const parsed = credentialsSchema.safeParse(raw);
				if (!parsed.success) return null;
				const { email, password } = parsed.data;
				const user = await prisma.user.findUnique({ where: { email } });
				if (!user) return null;
				const isValid = await compare(password, user.passwordHash);
				if (!isValid) return null;
				return {
					id: user.id,
					name: user.name ?? undefined,
					email: user.email,
					image: user.image ?? undefined,
					role: user.role,
				} as any;
			},
		}),
	],
	callbacks: {
		async jwt({ token, user }: any) {
			if (user) {
				token.id = user.id;
				token.role = user.role;
			}
			return token;
		},
		async session({ session, token }: any) {
			if (session.user) {
				(session.user as any).id = token.id;
				(session.user as any).role = token.role;
			}
			return session;
		},
	},
	pages: {
		signIn: "/signin",
	},
	secret: process.env.NEXTAUTH_SECRET,
	session: { strategy: "jwt" },
} satisfies Parameters<typeof NextAuth>[0];

const handler = NextAuth(authOptions);

export { handler as GET, handler as POST };