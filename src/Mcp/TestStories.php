<?php

namespace Josephdlmd\FilamentStorybook\Mcp;

use Filament\Facades\Filament;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Josephdlmd\FilamentStorybook\Accessibility;
use Josephdlmd\FilamentStorybook\Livewire\StoryCanvas;
use Josephdlmd\FilamentStorybook\Pages\Storybook;
use Josephdlmd\FilamentStorybook\SettledExceptions;
use Josephdlmd\FilamentStorybook\Stories;
use Josephdlmd\FilamentStorybook\StoryRegistry;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Livewire\Livewire;
use ReflectionClass;
use Throwable;

#[IsReadOnly]
class TestStories extends Tool
{
    protected string $name = 'storybook-test';

    protected string $description = 'Render stories through the real Storybook canvas and report which pass and which fail, with the error and the line that caused it. Run it after adding or changing a story, or a component or enum a story uses. Every finding fails, unless the owner settled it in config/storybook-exceptions.php. Fix every failure and run it again until all pass.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'ids' => $schema->array()->items($schema->string())->description('Pattern ids from storybook-list. Omit to test every story.'),
            'a11y' => $schema->boolean()->description('Also check each story against WCAG 2.2 AA with axe-core. Defaults to true.'),
        ];
    }

    public function handle(Request $request): Response
    {
        $all = app(StoryRegistry::class)->all();
        $ids = array_values(array_filter((array) $request->get('ids', [])));

        if ($unknown = array_diff($ids, array_keys($all))) {
            return Response::error('Unknown pattern ids: '.implode(', ', $unknown).'. Valid ids: '.implode(', ', array_keys($all)).'.');
        }

        // Fields and columns may read the current panel while rendering, which a console process lacks.
        if ($panel = Storybook::panel()) {
            Filament::setCurrentPanel($panel);
        }

        $passing = [];
        $failing = [];
        $warnings = [];
        $rendered = [];

        foreach ($ids === [] ? $all : array_intersect_key($all, array_flip($ids)) as $id => $stories) {
            $warnings = [...$warnings, ...static::warnings($id, $stories, app(StoryRegistry::class)->manifest($stories))];

            foreach ($stories->stories() as $name => $story) {
                if (Stories::kind($story) === null) {
                    $failing["{$id}.{$name}"] = 'Not a story: return a Filament component, table column or action, or take and return a Table or Schema, at '
                        .static::relative((string) $story->getFileName()).':'.$story->getStartLine();

                    continue;
                }

                try {
                    $rendered["{$id}.{$name}"] = (string) Livewire::mount(StoryCanvas::class, ['stories' => $stories::class, 'story' => $name]);
                    $passing[] = "{$id}.{$name}";
                } catch (Throwable $e) {
                    // Render errors arrive wrapped in view exceptions; the innermost one names the actual mistake.
                    while ($e->getPrevious() !== null) {
                        $e = $e->getPrevious();
                    }

                    $failing["{$id}.{$name}"] = class_basename($e).': '.$e->getMessage().' at '.static::location($e, $stories);
                }
            }
        }

        [$issues, $unchecked] = $request->get('a11y', true) && $rendered !== [] ? Accessibility::check($rendered) : [[], null];
        [$checks, $settled] = SettledExceptions::apply(array_map(fn (string $warning): string => "- {$warning}", $warnings), stories: true, full: $ids === []);

        // Strict mode: accessibility that couldn't be checked fails too, and no exception settles it.
        if ($unchecked) {
            $checks[] = "- `accessibility` `a11y.unchecked`: {$unchecked}";
        }

        $text = '# '.count($passing).' passing, '.(count($failing) + count($checks)).' failing, '.count($issues).' accessibility issues, '.count($settled)." settled exceptions\n";

        if ($failing !== []) {
            $text .= "\n## Failing\n\n"
                .implode("\n", array_map(fn (string $story, string $error): string => "- `{$story}`: {$error}", array_keys($failing), $failing))
                ."\n\nFix each failure, then run storybook-test again until every story passes.\n";
        }

        if ($checks !== []) {
            $text .= "\n## Checks\n\nThese stories render but break a house rule, named by the check's id. Fix each one; only the owner may settle one as an exception.\n\n"
                .implode("\n", $checks)."\n";
        }

        if ($issues !== []) {
            $text .= "\n## Accessibility\n\nThese stories break a WCAG 2.2 AA rule (axe-core). Fix each one, then run storybook-test again.\n\n"
                .implode("\n", $issues)."\n";
        }

        if ($settled !== []) {
            $text .= "\n## Settled exceptions\n\n".implode("\n", $settled)."\n";
        }

        return Response::text($text.($passing === [] ? '' : "\n## Passing\n\n".implode("\n", array_map(fn (string $story): string => "- `{$story}`", $passing))."\n"));
    }

    /**
     * House rules a story can break and still render. Agents copy stories, so a broken rule spreads to every screen.
     *
     * @param  array{description: string, stories: list<array{name: string, description: string, kind: string|null, snippet: string}>}  $manifest
     * @return list<string>
     */
    protected static function warnings(string $id, Stories $stories, array $manifest): array
    {
        $warnings = [];

        if (blank($manifest['description'])) {
            $warnings[] = "`{$id}` `story.description`: add description(), saying when to use this pattern and when not to.";
        }

        foreach ($manifest['stories'] as $i => $story) {
            $key = "`{$id}.{$story['name']}`";

            if ($i > 0 && blank($story['description'])) {
                $warnings[] = "{$key} `story.docblock`: add a one-line docblock saying how this variant differs.";
            }

            if (preg_match_all("/->helperText\\('([^']*)'/", $story['snippet'], $hints)) {
                foreach ($hints[1] as $hint) {
                    if (preg_match('/\\.(\\s|$)|\\b(e\\.g|i\\.e|etc)\\b/i', $hint)) {
                        $warnings[] = "{$key} `story.helper-text`: helper text is one short line with no full stop and no \"e.g.\" or \"etc\"; say \"For example, …\" (GOV.UK text input).";
                    }
                }
            }

            if (str_contains($story['snippet'], '->native(false)')) {
                $warnings[] = "{$key} `story.native-date`: native(false) makes a date that cannot be typed; use the native picker (Carbon and USWDS date pickers).";
            }

            if (preg_match('/(?<![\\w>:])round\\(/', $story['snippet'])) {
                $warnings[] = "{$key} `story.float-money`: round() works on floats, which drift by centavos; use decimal arithmetic rounded half up once, as `DocumentTotals` in `forms-document-totals` does (business standards: never floating-point money).";
            }

            if (str_contains($story['snippet'], 'Toggle::make(')) {
                $warnings[] = "{$key} `story.toggle`: a toggle is only for something that applies at once; a yes/no saved with a form is a Checkbox (Carbon toggle and checkbox).";
            }

            // Each component's chain runs to the next ::make(), so one component's option doesn't count for another.
            $chains = preg_split('/(?=\\b\\w+::make\\()/', $story['snippet']);

            if (collect($chains)->contains(fn (string $chain): bool => str_contains($chain, 'FileUpload::make(') && (! preg_match('/->(acceptedFileTypes|image)\\(/', $chain) || ! str_contains($chain, '->maxSize(')))) {
                $warnings[] = "{$key} `story.upload-limits`: an upload states its accepted types and largest size (USWDS file input).";
            }

            if (str_contains($story['snippet'], '->hiddenLabel(')) {
                $warnings[] = "{$key} `story.hidden-label`: keep the visible label; hiddenLabel() leaves the field unnamed on screen (Carbon forms).";
            }

            // Each field's chain runs to the next ::make(); a checkbox is a yes or no, never optional. A filter's fields are all
            // optional by nature, so the table's ->filters([...]) is left out.
            if (preg_match_all('/(?<![\\w\\\\])(?:TextInput|Textarea|Select|DatePicker|DateTimePicker|TimePicker|FileUpload|Radio|CheckboxList|ToggleButtons|TagsInput)::make\\(\'(\\w+)\'\\)((?:(?!::make\\().)*)/s', preg_replace('/^(\\s*)->filters\\(\\[.*?^\\1\\](?:, layout: [^\\n]*)?\\)/ms', '', $story['snippet']), $fields, PREG_SET_ORDER)) {
                foreach ($fields as [, $name, $chain]) {
                    if (str_contains($chain, '->placeholder(')) {
                        $warnings[] = "{$key} `story.placeholder`: the field `{$name}` has a placeholder(); put the example in helperText(), worded \"For example, …\" (USWDS, GOV.UK).";
                    }

                    if (! preg_match('/->(required|readOnly|disabled)\\((?!false\\))|\\(optional\\)/', $chain)) {
                        $warnings[] = "{$key} `story.optional-label`: the field `{$name}` is neither required() nor labelled \"(optional)\"; label an optional field \"… (optional)\" and leave required ones unmarked (Carbon form: one convention throughout).";
                    }
                }
            }

            if (preg_match("/->(label|modalSubmitActionLabel)\\('(ok|okay|yes|done|submit|confirm)'\\)/i", $story['snippet'], $vague)) {
                $warnings[] = "{$key} `story.vague-label`: a button labelled \"{$vague[2]}\" doesn't say what it does; use a verb, such as \"Approve order\" (Carbon modal and forms).";
            }

            if (preg_match_all("/(?:->(?:label|heading|modalHeading)|Section::make)\\('([^']+)'/", $story['snippet'], $texts)) {
                foreach (array_filter($texts[1], fn (string $text): bool => AuditPages::isTitleCase($text, $text)) as $text) {
                    $warnings[] = "{$key} `story.title-case`: \"{$text}\" is in Title Case; write labels and headings in sentence case (Carbon content).";
                }
            }

            if ($story['kind'] === 'table' && preg_match('/fn \\((?!\\?)[\\w\\\\]+ \\$record\\)/', $story['snippet'], $typed)) {
                $warnings[] = "{$key} `story.nullable-record`: `{$typed[0]}` gets null while the table has no rows, which stops an empty list rendering; make the record nullable, `?` before the type.";
            }

            if (preg_match("/lorem ipsum|'(option|section|item|step|label|title|text) \\d+'/i", $story['snippet'], $filler)) {
                $warnings[] = "{$key} `story.sample-content`: \"{$filler[0]}\" is placeholder content; use realistic content from a real task, so the story shows whether the pattern reads clearly (Carbon story guidelines).";
            }

            // Inline padding or margin, or a Tailwind spacing class, on any extra*Attributes(): spacing the theme doesn't set.
            if (preg_match('/->extra\\w*Attributes\\((?:(?!->).)*?(?:padding|margin|[\'"\\s](?:-?[pm][xytblrse]?|gap|space-[xy])-[\\w\\[\\].]+)/s', $story['snippet'])) {
                $warnings[] = "{$key} `story.own-spacing`: sets its own padding or margin; leave spacing to the theme's scale, through Filament's sections, groups and columns (Carbon spacing).";
            }

            // Inline font size, weight or colour, or a Tailwind class for them, on any extra*Attributes(): type and colour the theme doesn't set.
            if (preg_match('/->extra\\w*Attributes\\((?:(?!->).)*?(?:font-size|font-weight|(?<![\\w-])color\\s*:|background|[\'"\\s](?:text-(?:xs|sm|base|lg|\\d?xl|\\[[^\\]]+\\])|font-(?:thin|extralight|light|normal|medium|semibold|bold|extrabold|black)|(?:text|bg|border)-(?:[a-z]+-\\d{2,3}|white|black|\\[#[^\\]]+\\]))[\'"\\s])/s', $story['snippet'])) {
                $warnings[] = "{$key} `story.own-type`: sets its own font size, weight or colour; use Filament's size(), weight() and color() options, which take the theme's type styles and colour tokens and follow dark mode (Carbon typography and colour).";
            }

            if (collect($chains)->contains(fn (string $chain): bool => str_contains($chain, '->iconButton()') && ! str_contains($chain, '->tooltip('))) {
                $warnings[] = "{$key} `story.icon-tooltip`: an icon-only action needs a tooltip saying what it does, `->tooltip('…')`, shown on hover and focus (Carbon tooltip).";
            }

            if (substr_count($story['snippet'], 'ActionGroup::make(') > 1 && ! str_contains($story['snippet'], 'dropdown(false)')) {
                $warnings[] = "{$key} `story.nested-menus`: a menu opens inside another menu; give the inner `ActionGroup` `->dropdown(false)`, so its actions sit in the outer menu under a divider (Carbon disclosures: \"Do not nest one disclosure within another\").";
            }

            // A callout's chain runs to the next ::make() or to the action that holds it, whose $action->success() isn't the callout's.
            if (preg_match("/Callout::make\\((?:(?!::make\\(|->action\\().)*?->(?:success\\(\\)|danger\\(\\)|color\\('(?:success|danger)'\\))/s", $story['snippet'])) {
                $warnings[] = "{$key} `story.callout-status`: a callout is info or warning only; say how something went in a toast (Carbon notification: \"The callout variant only includes informational and warning statuses\").";
            }

            if (preg_match('/(?:Radio|CheckboxList)::make\\((?:(?!::make\\().)*?->inline\\(\\)/s', $story['snippet'])) {
                $warnings[] = "{$key} `story.inline-options`: stack radio and checkbox options, without inline() (Carbon checkbox and forms: \"arrange the checkbox and radio button groups vertically\").";
            }

            if (preg_match_all("/Step::make\\('([^']{17,})'\\)/", $story['snippet'], $steps)) {
                foreach ($steps[1] as $step) {
                    $warnings[] = "{$key} `story.wizard-step-name`: the wizard step \"{$step}\" is longer than 16 characters; name it in one or two words (Carbon progress indicator).";
                }
            }

            if (str_contains($story['snippet'], 'FiltersLayout::AboveContentCollapsible')) {
                $warnings[] = "{$key} `story.collapsed-filters`: AboveContentCollapsible puts the filter button alone in a row above the search, with a count even when no filter applies; show the filters open with `FiltersLayout::AboveContent` (Carbon filtering: \"horizontally at the top of the data set\").";
            }

            if (collect($chains)->contains(fn (string $chain): bool => str_contains($chain, '->collapsible()') && ! str_contains($chain, '->collapsed('))) {
                $warnings[] = "{$key} `story.collapsible-open`: a collapsible section starts collapsed, `->collapsed()`; one that should start open isn't collapsible (Carbon accordion).";
            }

            // A tooltip on a column, an entry or a hint icon shows on mouse hover only; on an action it is on a button.
            if (preg_match('/->hintIcon\\([^;]*?tooltip:|->hintIconTooltip\\(|(?:Column|Entry)::make\\((?:(?!::make\\().)*?->tooltip\\(/s', $story['snippet'])) {
                $warnings[] = "{$key} `story.hover-tooltip`: a tooltip on a column, entry or hint icon shows on mouse hover only; put the text in helperText() or the column's description() (WCAG 2.1.1; Carbon tooltip).";
            }

            if (preg_match_all("/->keyBindings\\(([^)]*)\\)/", $story['snippet'], $bindings) && preg_match("/'(?!(?:mod|ctrl|command|meta|alt|option)\\+)[^']+'/", implode(',', $bindings[1]))) {
                $warnings[] = "{$key} `story.shortcut-modifier`: a keyboard shortcut needs Ctrl, ⌘ or Alt, such as `mod+s`; a key alone fires while the user types or dictates (WCAG 2.1.4).";
            }

            if (preg_match("/->title\\('[^']*\\.'\\)/", $story['snippet'])) {
                $warnings[] = "{$key} `story.notification-full-stop`: a notification title has no full stop (Carbon notification).";
            }

            // A table with its own ->query() or ->relationship(), as a report has, shows the rows of its example models.
            if (in_array($story['kind'], ['column', 'table'], true) && $stories->records() === [] && ! str_contains($story['snippet'], '->query(') && ! str_contains($story['snippet'], '->relationship(')) {
                $warnings[] = "{$key} `story.sample-rows`: add sample rows in records(), or a ->query() on an example model, so the table shows real values.";
            }
        }

        return $warnings;
    }

    /** Points at the story's own line when the error passed through it, since that is what the agent can fix. */
    protected static function location(Throwable $e, Stories $stories): string
    {
        $file = (new ReflectionClass($stories))->getFileName();
        $frame = collect([['file' => $e->getFile(), 'line' => $e->getLine()], ...$e->getTrace()])
            ->first(fn (array $frame): bool => ($frame['file'] ?? null) === $file)
            ?? ['file' => $e->getFile(), 'line' => $e->getLine()];

        return static::relative($frame['file']).':'.$frame['line'];
    }

    protected static function relative(string $file): string
    {
        return Str::after($file, base_path().DIRECTORY_SEPARATOR);
    }
}
