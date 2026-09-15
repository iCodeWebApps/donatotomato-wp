#!/usr/bin/env node
// Fails the build on Plugin Check findings. `wp plugin check` exits 0 whatever
// it reports, so CI saves its strict-json output and this script decides:
// any ERROR fails, and any WARNING not listed in plugin-check-baseline.json
// fails. A listed warning that is no longer reported also fails, so the
// baseline can only shrink.
//
// Usage: node bin/plugin-check-gate.mjs <plugin-check output> <baseline json>
import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'

// The findings array from the command output, which wp-env wraps in status
// lines. A clean run prints a success line instead of JSON. Anything else
// throws: output the gate cannot read must not pass.
export function parseFindings(text) {
  const lines = text.replace(/\x1b\[[0-9;]*m/g, '').split(/\r?\n/)
  for (const line of lines) {
    const start = line.indexOf('[{')
    if (start >= 0) return JSON.parse(line.slice(start))
    if (line.trim() === '[]') return []
  }
  if (lines.some((line) => line.includes('Checks complete. No errors found.'))) return []
  throw new Error('No Plugin Check results found in the output')
}

export function evaluate(findings, baseline) {
  const key = (entry) => `${entry.file} ${entry.code}`
  const seen = new Map(baseline.map((entry) => [key(entry), 0]))
  const failures = []
  for (const finding of findings) {
    const where = `${finding.file}:${finding.line} ${finding.code}: ${finding.message}`
    if (finding.type === 'ERROR') failures.push(`ERROR ${where}`)
    else if (seen.has(key(finding))) seen.set(key(finding), seen.get(key(finding)) + 1)
    else failures.push(`New WARNING ${where}`)
  }
  for (const [entry, count] of seen) {
    if (count === 0) failures.push(`Baseline entry no longer reported; remove it from the baseline: ${entry}`)
  }
  return failures
}

if (process.argv[1] && fileURLToPath(import.meta.url) === process.argv[1]) {
  const [resultsPath, baselinePath] = process.argv.slice(2)
  const baseline = JSON.parse(readFileSync(baselinePath, 'utf8')).warnings
  let failures
  try {
    failures = evaluate(parseFindings(readFileSync(resultsPath, 'utf8')), baseline)
  } catch (error) {
    failures = [error.message]
  }
  if (failures.length > 0) {
    for (const failure of failures) console.error(failure)
    process.exit(1)
  }
  console.log(`Plugin Check: no errors and no warnings beyond the ${baseline.length} baseline entries.`)
}
