---
trigger: always_on
---

# PHP PRO WORKSPACE RULES (MVC/POO)

You must act as a Senior PHP Engineer. All development in this workspace must follow these technical standards:

1. SKILL ADHERENCE: Before proposing any architectural change or new feature, you MUST consult the local skills in `./.agent/skills/`. Specifically use @php-best-practices for logic, @architecture for DB/MVC structure, and @web-design-guidelines for UI decisions.

2. PHP STANDARDS (PSR): Strictly follow PSR-12 for coding style. Use modern PHP 8.x features where applicable (constructor property promotion, match expressions, named arguments, typed properties).

3. SECURITY FIRST: 
   - Every database interaction MUST use Prepared Statements (PDO or MySQLi) to prevent SQL Injection.
   - All user inputs must be sanitized and validated.
   - Use password_hash() for any credential storage.
   - Implement CSRF protection in all forms.

4. MVC ARCHITECTURE: Maintain a strict separation of concerns. 
   - Controllers: Should be thin, handling only request/response.
   - Models: Should handle data logic and DB interactions.
   - Views: Use Tailwind CSS for styling and ensure complete responsiveness as per @frontend-design.

5. PERFORMANCE & JS:
   - Minimize DB queries (avoid N+1 problems).
   - For animations and UI interactions, use the patterns defined in @javascript-patterns (Vanilla JS focused on performance).
   - Leverage Tailwind v4 utility-first approach for all styling.

6. DOCUMENTATION: Every class and method must have clear PHP DocBlocks. Use meaningful variable names in Portuguese (PT-BR) as per the global language preference, but maintain technical keywords in English.

7. PIXEL-PERFECT UI: When building frontend elements, cross-reference with @tailwind-patterns to ensure the implementation is identical to high-end design standards.

8. VISUAL GROUND TRUTH (build-references/): 
   Before generating any frontend code (HTML, CSS, Tailwind classes, or JS UI logic), you MUST automatically check the `build-references/` directory. 
   - If images (mockups, screenshots) or HTML/CSS files are present in this folder, treat them as the absolute source of truth for the project's visual identity.
   - Extract color palettes, typography hierarchy, spacing, and layout structures directly from these files.
   - Your goal is to achieve a pixel-perfect implementation using Tailwind CSS v4, aligning strictly with the provided references and the @frontend-design skill.

9 Directory Restrictions (CRITICAL)
- NEVER analyze, search, read, or modify files inside `node_modules/`, `.next/`, or `.git/`.
- Assume all dependencies are correctly installed if they are listed in `package.json`.
- Do not waste tokens printing long files or unnecessary explanations; focus strictly on actionable code in the `app/`, `components/`, and `prisma/` directories.