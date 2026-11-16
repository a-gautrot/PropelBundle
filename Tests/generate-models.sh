#!/usr/bin/env bash
set -euo pipefail

project_root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
model_directory="$project_root/Tests/Fixtures/Model"
php_binary="${PHP_BINARY:-php}"
temporary_root="$(cd -- "${TMPDIR:-/tmp}" && pwd)"
generation_directory="$(mktemp -d "$temporary_root/propel-fixtures.XXXXXXXX")"

cleanup() {
    case "$generation_directory" in
        "$temporary_root"/propel-fixtures.*)
            rm -rf -- "$generation_directory"
            ;;
    esac
}
trap cleanup EXIT

cd -- "$project_root"
"$php_binary" Tests/build-models.php "$generation_directory"

generated_models="$generation_directory/Propel/Bundle/PropelBundle/Tests/Fixtures/Model"
generated_files=(
    Base/Book.php Base/BookQuery.php Map/BookTableMap.php
    Base/User.php Base/UserQuery.php Map/UserTableMap.php
)

# Validate the complete output before replacing any existing fixture.
for file in "${generated_files[@]}"; do
    if [[ ! -f "$generated_models/$file" ]]; then
        printf 'Propel did not generate the expected file: %s\n' "$file" >&2
        exit 1
    fi
done

for file in "${generated_files[@]}"; do
    mkdir -p -- "$(dirname -- "$model_directory/$file")"
    cp -- "$generated_models/$file" "$model_directory/$file"
done

# Preserve custom model methods and the fake queries used by unit tests.
for file in Book.php BookQuery.php User.php UserQuery.php; do
    if [[ ! -e "$model_directory/$file" ]]; then
        cp -- "$generated_models/$file" "$model_directory/$file"
    fi
done

printf 'Book and User fixture models generated successfully.\n'
