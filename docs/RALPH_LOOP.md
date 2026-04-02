# Ralph Loop

This repository keeps one iterative loop in the Cursor overlay: **Ralph**.

## Start

```bash
bash scripts/setup-ralph-loop.sh "Fix the task" --completion-promise "DONE" --max-iterations 20
```

## Stop conditions

- Ralph continues the same task in the same Cursor session.
- The stop hook releases the session only when:
  - the transcript contains the completion promise, ideally as `<promise>DONE</promise>`, or
  - `max_iterations` is reached, or
  - the operator cancels the loop.

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
