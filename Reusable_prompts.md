# When to use it: once per phase, right before you start executing that phase's sessions.
# Timeline:
- After Claude Code generates all 10 phase-N.md files → you review → approve
- /clear
- Paste that prompt with Phase 1 → get all task-1-X.md files → review → commit
- /clear
- Start executing Session 1 with the short execute prompt (Execute task 1.1 from...)
- Work through all Phase 1 sessions
- When Phase 1 is done and committed → /clear
- Paste the prompt again with Phase 2 → get all task-2-X.md files
- Repeat

## This is the Prompt u should use : 
Now generate all task-X-X.md files for Phase [5] only.
Location: docs/tasks/phase-[5]/
Format: prompt-style, not documentation. Each file must include:
- Context (1-2 lines)
- Task (imperative)
- Requirements (bullet list, spec § references not copy-paste)
- Do NOT list
- Acceptance (Pest tests + behavior)
- Reference sections in SPEC.md

Keep each under 60 lines. When done, stop. Do not write code.
====================================== 
## check if need to clear in the current tasks 
- look at next task ,can u do it in this context ? or  u need to do a context file or something after clear command ? check it out and make sure 100% that u answer me correctly

## prompt after clearing mid-phase : 
Read .claude/task-context.md then execute Task 1.6 per docs/tasks/phase-1/task-1-6.md and CLAUDE.md/SPEC.md.

======================================
# Rusable Executing Task prompt : 
## Phase 5
Execute the next unchecked task in docs/phases/phase-5.md.
Read the corresponding task file in docs/tasks/phase-5/.
Follow CLAUDE.md and SPEC.md strictly.
Write Pest tests, run them, then  commit message and wait for me to give you the next task.
When I approve, tick the checkbox in phase-5.md and update .claude/task-context.md with the completed task, key file locations, and current test count.
