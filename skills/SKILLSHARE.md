# Skillshare Integration

This package includes Agent Skills that can be installed and managed using Skillshare.

## What is Skillshare?

Skillshare is a CLI tool that manages and syncs AI skills across 50+ tools (Claude, Cursor, Windsurf, etc.) from a single source. It handles installation, updates, and synchronization automatically.

## Quick Start

### Install from GitHub

Install skills directly from the GitHub repository:

```bash
# Install all skills globally
skillshare install code-soup/codesoup-options --all

# Or select specific skills
skillshare install code-soup/codesoup-options -s codesoup-options-setup,codesoup-options-usage

# Sync to all configured AI tools
skillshare sync
```

### Install from Local Repository

If you've cloned this repository locally:

```bash
# Install from local path
skillshare install ./path/to/codesoup-options/skills --all

# Or install and track for updates
skillshare install ./path/to/codesoup-options/skills --all --track
```

### Project-Level Installation

Install skills to a specific project only:

```bash
# Initialize project-level skillshare (first time only)
skillshare init -p --targets "claude,cursor"

# Install skills to project
skillshare install code-soup/codesoup-options --all -p

# Sync to project targets
skillshare sync
```

### Track for Updates

Install and track skills for automatic updates:

```bash
# Install with tracking enabled
skillshare install code-soup/codesoup-options --all --track

# Check for updates
skillshare check

# Update all tracked skills
skillshare update --all && skillshare sync
```

## Available Skills

This package includes three skills:

1. **codesoup-options-setup** - Set up and configure the plugin
2. **codesoup-options-usage** - Retrieve and save options using the API
3. **codesoup-options-migration** - Migrate configurations when changing settings

## Manual Installation

If you prefer not to use Skillshare, you can manually install the skills:

```bash
# Symlink (recommended)
ln -s vendor/codesoup/options/skills ./skills/codesoup-options

# Or copy
cp -r vendor/codesoup/options/skills ./skills/codesoup-options
```

## Learn More

- Skillshare Documentation
- Agent Skills Specification
- [Skills README](README.md)

## For Developers

This package distributes skills for others to install. The `skills/` directory contains:

- Individual skill directories with SKILL.md files
- Examples in markdown format
- All following the Agent Skills specification

Users install directly from the repository - no `.skillshare/` directory needed in the source.

