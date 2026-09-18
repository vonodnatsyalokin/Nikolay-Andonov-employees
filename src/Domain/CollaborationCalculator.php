<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Works out how long every pair of employees spent on the same projects
 * at the same time.
 *
 * Two employees only ever overlap inside one project, so the records are
 * grouped by project first and compared pair by pair inside each group.
 */
final class CollaborationCalculator
{
    /**
     * @param iterable<EmploymentRecord> $records
     *
     * @return list<PairCollaboration> longest total first
     */
    public function calculate(iterable $records): array
    {
        /** @var array<string, EmployeePair> $pairs */
        $pairs = [];

        /** @var array<string, array<int, int>> $daysPerProject pair key => project id => days */
        $daysPerProject = [];

        foreach ($this->groupByProject($records) as $projectId => $group) {
            $count = count($group);

            for ($i = 0; $i < $count - 1; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $one = $group[$i];
                    $other = $group[$j];

                    // The same employee can appear twice on a project, e.g. after rejoining it.
                    if ($one->employeeId === $other->employeeId) {
                        continue;
                    }

                    $days = $one->period->overlapDaysWith($other->period);

                    if ($days === 0) {
                        continue;
                    }

                    $pair = new EmployeePair($one->employeeId, $other->employeeId);
                    $key = $pair->key();

                    $pairs[$key] ??= $pair;
                    $daysPerProject[$key][$projectId] = ($daysPerProject[$key][$projectId] ?? 0) + $days;
                }
            }
        }

        return $this->sort($this->build($pairs, $daysPerProject));
    }

    /**
     * @param iterable<EmploymentRecord> $records
     *
     * @return array<int, list<EmploymentRecord>>
     */
    private function groupByProject(iterable $records): array
    {
        $groups = [];

        foreach ($records as $record) {
            $groups[$record->projectId][] = $record;
        }

        return $groups;
    }

    /**
     * @param array<string, EmployeePair>    $pairs
     * @param array<string, array<int, int>> $daysPerProject
     *
     * @return list<PairCollaboration>
     */
    private function build(array $pairs, array $daysPerProject): array
    {
        $collaborations = [];

        foreach ($pairs as $key => $pair) {
            $days = $daysPerProject[$key];
            ksort($days);

            $projects = [];

            foreach ($days as $projectId => $projectDays) {
                $projects[] = new ProjectCollaboration($projectId, $projectDays);
            }

            $collaborations[] = new PairCollaboration($pair, $projects);
        }

        return $collaborations;
    }

    /**
     * Longest total first. Employee ids break a tie, so the order never depends
     * on how the file happened to be sorted.
     *
     * @param list<PairCollaboration> $collaborations
     *
     * @return list<PairCollaboration>
     */
    private function sort(array $collaborations): array
    {
        usort($collaborations, static function (PairCollaboration $a, PairCollaboration $b): int {
            if ($a->totalDays !== $b->totalDays) {
                return $b->totalDays <=> $a->totalDays;
            }

            return [$a->pair->firstEmployeeId, $a->pair->secondEmployeeId]
                <=> [$b->pair->firstEmployeeId, $b->pair->secondEmployeeId];
        });

        return $collaborations;
    }
}
