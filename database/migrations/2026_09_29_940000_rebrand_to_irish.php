<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Renames the demonstration clinic from Patrice Beauty Lounge Aesthetics to
 * Irish Aesthetics and Beauty Lounge, on installations that are already
 * seeded.
 *
 * A rebrand is a data migration, not only a config edit, because almost
 * nothing about the old name lives in config. The clinic's legal pages and
 * journal posts are rows an office can edit, so they are in the database; the
 * demo logins are seeded rows too. Editing config and the seeders alone would
 * fix a fresh `db:seed` and leave every already-deployed site still saying
 * Patrice, which is the state nobody would notice until a customer did.
 *
 * The organization slug is the sharp edge. config('clinic.organization') is
 * looked up to resolve the current organization on nearly every request, so
 * the config and this row have to move together -- a half-applied rename here
 * returns null from that lookup and takes the whole site down. The row is
 * matched on its old slug and updated in place, keeping its id, so every
 * branch, employee and record that points at it stays valid.
 *
 * Copy is matched exactly, phrase by phrase, rather than by replacing the
 * substring "Patrice" everywhere. A blind replace would also rewrite any
 * occurrence in content an office had since edited themselves, and would turn
 * "Patrice" the name into "Irish" inside a sentence where it is some other
 * word entirely. Each replacement below is the string the seeder writes.
 */
return new class extends Migration
{
    /** The organization slug this installation had before the rename. */
    private const OLD_SLUG = 'patrice';

    private const NEW_SLUG = 'irish';

    /**
     * Content columns to rewrite, as table => columns. Only text the seeder
     * authored is touched.
     *
     * @var array<string, list<string>>
     */
    private const CONTENT = [
        'pages' => ['title', 'summary', 'sections'],
        'promotions' => ['title', 'summary', 'description', 'details'],
        'treatments' => ['summary', 'description'],
        'treatment_categories' => ['description'],
        'specialists' => ['bio'],
        'faqs' => ['question', 'answer'],
    ];

    /** @var array<string, string> old => new, longest first so a phrase wins over its own prefix */
    private const COPY = [
        'Patrice Beauty Lounge Aesthetics' => 'Irish Aesthetics and Beauty Lounge',
        'Patrice Wellness' => 'Irish Wellness',
        'Patrice clinics' => 'Irish clinics',
        'Patrice offers' => 'Irish offers',
        'at Patrice' => 'at Irish',
        'Patrice is' => 'Irish is',
    ];

    public function up(): void
    {
        $this->renameOrganization();
        $this->renameCopy();
        $this->renameDemoEmails();
    }

    public function down(): void
    {
        // The arguments are (from, to), so undoing the rename means starting
        // from the slug this migration put there -- NEW_SLUG -- and moving it
        // back to OLD_SLUG. Passing them in the same order as up() (which was
        // the bug here) searches for the OLD slug, matches no rows, and leaves
        // the organization called Irish after a rollback that reported itself
        // as successful. The site then cannot resolve its own organization and
        // every page that reads it goes down.
        $this->renameOrganization(self::NEW_SLUG, self::OLD_SLUG);
        $this->renameCopy(true);
        $this->renameDemoEmails(true);
    }

    /**
     * Moves the organization row itself, keeping its id so every branch,
     * employee and record pointing at it stays valid.
     *
     * `up()` matches the row by the slug it has *now* (the old one), not the
     * one the config was just changed to. Getting that backwards is silent:
     * the update matches nothing, the migration still reports DONE, and the
     * site is left unable to resolve its own organization.
     */
    private function renameOrganization(string $from = self::OLD_SLUG, string $to = self::NEW_SLUG): void
    {
        DB::table('organizations')
            ->where('slug', $from)
            ->update([
                'slug' => $to,
                'name' => $to === self::NEW_SLUG
                    ? 'Irish Aesthetics and Beauty Lounge'
                    : 'Patrice Beauty Lounge Aesthetics',
            ]);
    }

    /**
     * Rewrites the brand name in the text the seeder wrote.
     *
     * @param  bool  $reverse  undo rather than apply
     */
    private function renameCopy(bool $reverse = false): void
    {
        $pairs = self::COPY;

        if ($reverse) {
            // Flipped by hand, not with array_reverse: that turns the values
            // around but leaves every key still naming the OLD string, so the
            // search term would be "Patrice" and down() would match nothing.
            $pairs = [];
            foreach (self::COPY as $old => $new) {
                $pairs[$new] = $old;
            }
        }

        foreach (self::CONTENT as $table => $columns) {
            if (! DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! DB::getSchemaBuilder()->hasColumn($table, $column)) {
                    continue;
                }

                foreach ($pairs as $search => $replace) {
                    DB::table($table)->where($column, 'like', "%{$search}%")
                        ->update([$column => DB::raw(
                            "REPLACE(`{$column}`, '".addslashes($search)."', '".addslashes($replace)."')"
                        )]);
                }
            }
        }
    }

    /**
     * Moves the demo logins across so the seeded credentials still match the
     * seeder and the documentation after this runs.
     *
     * @param  bool  $reverse  undo rather than apply
     */
    private function renameDemoEmails(bool $reverse = false): void
    {
        [$from, $to] = $reverse
            ? ['@irish.test', '@patrice.test']
            : ['@patrice.test', '@irish.test'];

        foreach (['users', 'branches'] as $table) {
            if (! DB::getSchemaBuilder()->hasTable($table) || ! DB::getSchemaBuilder()->hasColumn($table, 'email')) {
                continue;
            }

            DB::table($table)->where('email', 'like', "%{$from}")->update([
                'email' => DB::raw("REPLACE(`email`, '{$from}', '{$to}')"),
            ]);
        }
    }
};