import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { parseFindings, evaluate } from '../bin/plugin-check-gate.mjs'

const baseline = [
  { file: 'readme.txt', code: 'trademarked_term' },
  { file: 'donatotomato.php', code: 'trademarked_term' },
]
const finding = (file, code, type = 'WARNING', message = 'message') => ({ file, line: 0, column: 0, type, code, message })
const known = [finding('readme.txt', 'trademarked_term'), finding('donatotomato.php', 'trademarked_term')]
// Shaped like CI: the JSON ends without a newline, so wp-env's status line
// follows it on the same line.
const output = (items) =>
  `ℹ Starting 'wp plugin check donatotomato' on the cli container.\n${JSON.stringify(items)}✔ Ran \`wp plugin check donatotomato\` in 'cli'. (in 2s 443ms)\n`

test('the output captured from a CI run parses', () => {
  const captured = readFileSync(new URL('./fixtures/plugin-check-ci.txt', import.meta.url), 'utf8')
  const findings = parseFindings(captured)
  assert.deepEqual(findings.map((f) => `${f.file} ${f.type} ${f.code}`), [
    'readme.txt WARNING trademarked_term',
    'donatotomato.php WARNING trademarked_term',
  ])
  assert.deepEqual(evaluate(findings, baseline), [])
})

test('only the baseline warnings: passes', () => {
  assert.deepEqual(evaluate(parseFindings(output(known)), baseline), [])
})

test('any ERROR fails, even in a baseline file', () => {
  const failures = evaluate(parseFindings(output([...known, finding('readme.txt', 'trademarked_term', 'ERROR')])), baseline)
  assert.equal(failures.length, 1)
  assert.match(failures[0], /^ERROR readme\.txt/)
})

test('a warning outside the baseline fails', () => {
  const failures = evaluate(parseFindings(output([...known, finding('readme.txt', 'upgrade_notice_limit')])), baseline)
  assert.equal(failures.length, 1)
  assert.match(failures[0], /^New WARNING readme\.txt:0 upgrade_notice_limit/)
})

test('brackets and quotes inside a message do not end the array early', () => {
  const tricky = finding('readme.txt', 'upgrade_notice_limit', 'WARNING', 'Use "[x]" not {y} \\"] here')
  const failures = evaluate(parseFindings(output([...known, tricky])), baseline)
  assert.equal(failures.length, 1)
  assert.match(failures[0], /upgrade_notice_limit: Use "\[x\]" not \{y\} \\"\] here$/)
})

test('a baseline entry that is no longer reported fails', () => {
  const failures = evaluate(parseFindings(output([known[0]])), baseline)
  assert.deepEqual(failures, ['Baseline entry no longer reported; remove it from the baseline: donatotomato.php trademarked_term'])
})

test('a clean run passes against an empty baseline', () => {
  assert.deepEqual(evaluate(parseFindings('Success: Checks complete. No errors found.\n'), []), [])
})

test('a clean run against a non-empty baseline fails as stale', () => {
  assert.equal(evaluate(parseFindings('Success: Checks complete. No errors found.\n'), baseline).length, 2)
})

test('an empty JSON array is a clean run', () => {
  assert.deepEqual(parseFindings(output([])), [])
})

test('output with no results fails closed', () => {
  assert.throws(() => parseFindings('Error: The "donatotomato" plugin does not exist.\n'), /No Plugin Check results/)
  assert.throws(() => parseFindings(''), /No Plugin Check results/)
})

test('truncated JSON fails closed', () => {
  assert.throws(() => parseFindings(`${JSON.stringify(known).slice(0, 60)}\n`))
})

test('colour codes around the JSON are ignored', () => {
  const coloured = `\x1b[36mℹ Starting\x1b[0m\n\x1b[0m${JSON.stringify(known)}\x1b[0m\n`
  assert.deepEqual(evaluate(parseFindings(coloured), baseline), [])
})
