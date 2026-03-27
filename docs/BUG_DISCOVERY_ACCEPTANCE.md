# Bug Discovery Acceptance (anti-self-deception gate)

Этот документ отделяет продуктовую приемку от процессной.

## Dual status (обязательно)

- `PROCESS_ACCEPT`: пайплайн/verify/артефакты выполнены.
- `PRODUCT_ACCEPT`: реальные продуктовые баги закрыты и discovery выполнен.

Финальный `ACCEPT` возможен только при обоих true.

## Обязательные блоки post-deploy

1) **Owner bug ledger**
- Для каждого бага Owner:
  - `bug_id`
  - `reproduce`
  - `root_cause`
  - `fix`
  - `verify_on` (device/viewport/browser)
  - `status`: `fixed` | `partially_fixed` | `not_fixed`

2) **Agent-found bugs (without owner hints)**
- Минимум 10 новых дефектов:
  - 3 interaction
  - 3 visual consistency
  - 2 mobile/desktop parity
  - 2 regression risk

Если блок пустой или формальный — `PRODUCT_ACCEPT=false`.

### Фильтрация quality (обязательно)

Для `agent_found_bugs` нужен отдельный filtered-файл с разложением:
- реальные продуктовые,
- технические (process/tooling),
- косметика,
- дубли owner-багов.

`PRODUCT_ACCEPT=true` запрещён, если discovery-квота набрана в основном дублями/мусором.

## Snapshot governance (blocking)

- Обновление baseline snapshots запрещено без product signoff.
- Signoff должен явно фиксировать:
  - почему новый baseline отражает целевой UI-state;
  - какие owner-баги это закрывает;
  - на каких девайсах/viewport это подтверждено.

Без signoff обновление baseline трактуется как легализация дефекта (`REJECT`).

## Safari/mobile gate (blocking)

Без явного статуса по **iPhone Safari** `PRODUCT_ACCEPT=true` запрещён.

Минимум требуется:
- device/browser: `iPhone Safari`,
- статусы для hostile-сценариев: `first_open`, `second_open`, `scroll`, `back`, `reopen`, `sticky_behavior`, `cta_honesty`,
- результат по каждому owner-багу: `fixed|partial|not_fixed`.
