# CodeSoup Options - Agent Skills

This directory contains Agent Skills for the CodeSoup Options WordPress plugin. These skills enable AI agents to understand and work with the plugin.

## What are Agent Skills?

Agent Skills are structured documentation files that AI agents can discover and use to understand how to work with your code. They follow the Agent Skills specification.

## Available Skills

### 1. codesoup-options-setup
**File:** `codesoup-options-setup/SKILL.md`

Set up and configure CodeSoup Options plugin for WordPress. Covers:
- ACF (Advanced Custom Fields) integration
- Native metaboxes without frameworks
- Custom integrations (CMB2, MetaBox.io, Carbon Fields)
- Menu placement and configuration
- Post types and capabilities

### 2. codesoup-options-usage
**File:** `codesoup-options-usage/SKILL.md`

Retrieve and save WordPress options using the Manager API. Covers:
- Getting single and bulk options
- Using options in templates
- Complete Manager API reference
- Saving options with native metaboxes
- Performance tips and troubleshooting

### 3. codesoup-options-migration
**File:** `codesoup-options-migration/SKILL.md`

Migrate configurations when changing post_type, prefix, or capabilities. Covers:
- Migration process and best practices
- Common migration scenarios
- Troubleshooting migration issues
- Custom migration logic
- Data structure migrations

## Installation

### Using Skillshare (Recommended)

Skillshare is a CLI tool that manages and syncs AI skills across 50+ tools from a single source.

**Install skills globally (available to all AI tools):**

```bash
# Install from GitHub
skillshare install code-soup/codesoup-options -s codesoup-options-setup,codesoup-options-usage,codesoup-options-migration

# Or install all skills
skillshare install code-soup/codesoup-options --all

# Sync to all configured AI tools
skillshare sync
```

**Install skills to project only:**

```bash
# Initialize project-level skillshare
skillshare init -p --targets "claude,cursor"

# Install skills to project
skillshare install code-soup/codesoup-options --all -p

# Sync to project targets
skillshare sync
```

**Track for updates:**

```bash
# Install and track for updates
skillshare install code-soup/codesoup-options --all --track

# Check for updates
skillshare check

# Update all tracked skills
skillshare update --all && skillshare sync
```

### Manual Installation

If you prefer not to use Skillshare:

**Option A: Symlink (recommended)**
```bash
ln -s vendor/codesoup/options/skills ./skills/codesoup-options
```

**Option B: Copy**
```bash
cp -r vendor/codesoup/options/skills ./skills/codesoup-options
```

**For Plugin Users:**

If you installed the plugin directly in WordPress, symlink or copy the skills directory to your workspace:

```bash
# From your workspace root
ln -s wp-content/plugins/codesoup-options/skills ./skills/codesoup-options
```

## Skill Structure

Each skill follows the Agent Skills specification with optional examples directory:

```
skills/
├── README.md (this file)
├── codesoup-options-setup/
│   ├── SKILL.md
│   └── examples/
│       ├── acf-integration.md
│       ├── native-metaboxes.md
│       ├── custom-integration.md
│       └── menu-placement.md
├── codesoup-options-usage/
│   ├── SKILL.md
│   └── examples/
│       ├── retrieving-options.md
│       └── saving-options.md
└── codesoup-options-migration/
    ├── SKILL.md
    └── examples/
        ├── basic-migration.md
        ├── prefix-only.md
        ├── capabilities-sync.md
        └── wpcli-migration.md
```

Examples are in markdown format and referenced from the main SKILL.md file.

## Skillshare Compatibility

This package is a skill repository that users can install via Skillshare. Users can:

- Install skills directly from the GitHub repository
- Track skills for automatic updates
- Sync skills across multiple AI tools
- Manage skills in both global and project-level modes

The `skills/` directory structure is automatically recognized by Skillshare when users run:
```bash
skillshare install code-soup/codesoup-options --all
```

## For Developers

### Updating Skills

When updating the plugin documentation, also update the corresponding skills:

1. Edit the relevant SKILL.md file
2. Update examples in the `examples/` directory
3. Update the frontmatter description if the skill's purpose changes
4. Update version in frontmatter metadata if making significant changes
5. Test with an AI agent to ensure clarity

### Skill Format

Each SKILL.md file follows the Agent Skills specification:

1. **YAML Frontmatter** - Metadata for skill discovery
   ```yaml
   ---
   name: skill-name
   description: What the skill does and when to use it (max 1024 chars)
   license: GPL-3.0-or-later
   metadata:
     author: code-soup
     version: "1.0.0"
     package: codesoup/options
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
- **Specification compliant**: Follow agentskills.io specification

### Publishing Skills

Skills are published by pushing to the GitHub repository. Users install directly from GitHub:

```bash
# Users install from GitHub
skillshare install code-soup/codesoup-options --all

# Or from a local clone
skillshare install ./path/to/codesoup-options/skills --all
```

## Learn More

- Agent Skills Specification
- What are Skills?
- Integrate Skills

## Traditional Documentation

For human developers, see the `docs/` directory for browsable documentation:
- [README.md](../README.md) - Main documentation
- [docs/acf.md](../docs/acf.md) - ACF integration
- [docs/native.md](../docs/native.md) - Native metaboxes
- [docs/custom-integrations.md](../docs/custom-integrations.md) - Custom integrations
- [docs/api.md](../docs/api.md) - API reference
- [docs/migration.md](../docs/migration.md) - Migration guide
- [docs/examples/](../docs/examples/) - Working code examples

