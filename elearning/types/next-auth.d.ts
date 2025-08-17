import NextAuth, { DefaultSession } from "next-auth";

declare module "next-auth" {
  interface Session {
    user: {
      id: string;
      role: "TEACHER" | "STUDENT";
    } & DefaultSession["user"];
  }

  interface User {
    id: string;
    role: "TEACHER" | "STUDENT";
  }
}

declare module "next-auth/jwt" {
  interface JWT {
    id: string;
    role: "TEACHER" | "STUDENT";
  }
}