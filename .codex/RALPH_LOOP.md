# Codex Ralph Loop

This repository includes a Codex-side Ralph adaptation.

Unlike the Cursor overlay, Codex does not use `.cursor/hooks.json`, so the Codex version is a stateful prompt loop driven by scripts in `scripts/`.

## Start

```bash
bash scripts/codex-ralph-start.sh "Fix the task" --completion-promise "DONE" --max-iterations 5
```

## Get the next prompt

```bash
bash scripts/codex-ralph-next.sh
```

This writes the next iteration prompt to `.codex/tmp/ralph-next-prompt.md` and prints it to stdout.

## Mark completion from a transcript/output file

```bash
bash scripts/codex-ralph-next.sh --transcript /path/to/output.txt
```

If the transcript contains `<promise>DONE</promise>`, the loop marks itself completed instead of issuing another prompt.

## Status

```bash
bash scripts/codex-ralph-status.sh
```

## Cancel

```bash
bash scripts/codex-ralph-cancel.sh
```

## Files

- State: `.codex/state/ralph-loop.local.json`
- Prompt artifact: `.codex/tmp/ralph-next-prompt.md`
- Log: `.codex/artifacts/ralph-loop.log`
