# CodeSoup Options - Agent Skills

This directory contains Agent Skills for the CodeSoup Options WordPress plugin. These skills enable AI agents to understand and work with the plugin.

## What are Agent Skills?

Agent Skills are structured documentation files that AI agents can discover and use to understand how to work with your code. They follow the [Agent Skills specification](https://agentskills.io).

## Available Skills

### 1. CodeSoup Options Setup
**File:** `codesoup-options-setup/SKILL.md`

Set up and configure CodeSoup Options plugin for WordPress. Covers:
- ACF (Advanced Custom Fields) integration
- Native metaboxes without frameworks
- Custom integrations (CMB2, MetaBox.io, Carbon Fields)
- Menu placement and configuration
- Post types and capabilities

### 2. CodeSoup Options Usage
**File:** `codesoup-options-usage/SKILL.md`

Retrieve and save WordPress options using the Manager API. Covers:
- Getting single and bulk options
- Using options in templates
- Complete Manager API reference
- Saving options with native metaboxes
- Performance tips and troubleshooting

### 3. CodeSoup Options Migration
**File:** `codesoup-options-migration/SKILL.md`

Migrate configurations when changing post_type, prefix, or capabilities. Covers:
- Migration process and best practices
- Common migration scenarios
- Troubleshooting migration issues
- Custom migration logic
- Data structure migrations

## Installation

### For Users (Composer Installation)

After installing the plugin via Composer, make the skills available to your AI agent:

**Option A: Symlink (recommended)**
```bash
ln -s vendor/codesoup/options/skills ./skills/codesoup-options
```

**Option B: Copy**
```bash
cp -r vendor/codesoup/options/skills ./skills/codesoup-options
```

### For Plugin Users

If you installed the plugin directly in WordPress, symlink or copy the skills directory to your workspace:

```bash
# From your workspace root
ln -s wp-content/plugins/codesoup-options/skills ./skills/codesoup-options
```

## Skill Structure

Each skill is self-contained with all information inline (no external references). This ensures portability regardless of where the skill is installed.

```
skills/
├── README.md (this file)
├── codesoup-options-setup/
│   └── SKILL.md
├── codesoup-options-usage/
│   └── SKILL.md
└── codesoup-options-migration/
    └── SKILL.md
```

## For Developers

### Updating Skills

When updating the plugin documentation, also update the corresponding skills:

1. Edit the relevant SKILL.md file
2. Keep all content inline (no external references)
3. Update the frontmatter description if the skill's purpose changes
4. Test with an AI agent to ensure clarity

### Skill Format

Each SKILL.md file has:

1. **YAML Frontmatter** - Metadata for skill discovery
   ```yaml
   ---
   name: Skill Name
   description: What the skill helps with (keywords for discovery)
   ---
   ```

2. **Markdown Content** - Complete, self-contained documentation
   - When to use this skill
   - Step-by-step instructions
   - Code examples
   - Troubleshooting
   - API reference (if applicable)

### Design Principles

- **Self-contained**: All information inline, no external references
- **Task-oriented**: Focus on "how to accomplish X"
- **Progressive disclosure**: Start simple, add detail as needed
- **Portable**: Works whether in vendor/, workspace/, or anywhere else
- **Keyword-rich descriptions**: Help agents discover the right skill

## Learn More

- [Agent Skills Specification](https://agentskills.io/specification)
- [What are Skills?](https://agentskills.io/what-are-skills)
- [Integrate Skills](https://agentskills.io/integrate-skills)

## Traditional Documentation

For human developers, see the `docs/` directory for browsable documentation:
- [README.md](../README.md) - Main documentation
- [docs/acf.md](../docs/acf.md) - ACF integration
- [docs/native.md](../docs/native.md) - Native metaboxes
- [docs/custom-integrations.md](../docs/custom-integrations.md) - Custom integrations
- [docs/api.md](../docs/api.md) - API reference
- [docs/migration.md](../docs/migration.md) - Migration guide
- [docs/examples/](../docs/examples/) - Working code examples

