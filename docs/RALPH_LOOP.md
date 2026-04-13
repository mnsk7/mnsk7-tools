# Ralph Loop

This repository keeps one iterative loop in the Cursor overlay: **Ralph**.

## Start

```bash
bash scripts/setup-ralph-loop.sh "Fix the task" --completion-promise "DONE" --max-iterations 20
```

## Stop conditions

- Ralph continues the same task in the same Cursor session.
- The stop hook releases the session only when:
  - the **last assistant message** (tylko bloki tekstowe, nie narzędzia) zawiera dokładnie tag: `<promise>DONE</promise>` (ten sam token co `--completion-promise`). Nie wystarczy sam substring w całym pliku — inaczej łapie echo skryptu / cytat w odpowiedzi.
  - `max_iterations` is reached, or
  - the operator cancels the loop.

**Zakończenie zadania:** w ostatniej wiadomości asystenta wstaw literalnie np. `<promise>DONE</promise>` (bez dodatkowych znaków w środku tagów).

## Cancel

```bash
bash scripts/cancel-ralph-loop.sh
```

## Recommended usage

- Use a precise task prompt.
- State file/runtime constraints explicitly.
- Name the checks Ralph should run.
- Always set `--max-iterations`.
- Use a single unambiguous completion promise.
