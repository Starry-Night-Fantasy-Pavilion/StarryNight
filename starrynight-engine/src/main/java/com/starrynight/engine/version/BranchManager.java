package com.starrynight.engine.version;

import lombok.Data;
import org.springframework.stereotype.Component;

import java.time.LocalDateTime;
import java.util.*;
import java.util.concurrent.ConcurrentHashMap;
import java.util.stream.Collectors;

@Component
public class BranchManager {

    private final Map<String, Branch> branches = new ConcurrentHashMap<>();
    private final Map<String, Commit> commits = new ConcurrentHashMap<>();
    private final Map<String, VersionSnapshot> snapshots = new ConcurrentHashMap<>();

    public Branch createBranch(String novelId, String name, String baseVersionId, String description) {
        Branch branch = new Branch();
        branch.setId(UUID.randomUUID().toString());
        branch.setNovelId(novelId);
        branch.setName(name);
        branch.setDescription(description);
        branch.setBaseVersionId(baseVersionId);
        branch.setStatus(Branch.BranchStatus.ACTIVE);
        branch.setCreatedAt(LocalDateTime.now());

        branches.put(branch.getId(), branch);
        return branch;
    }

    public Commit commit(String branchId, String nodeType, String nodeId,
                         Commit.ChangeType changeType, String contentBefore,
                         String contentAfter, String message, Commit.Author author) {
        Branch branch = branches.get(branchId);
        if (branch == null) {
            throw new IllegalArgumentException("Branch not found: " + branchId);
        }

        Commit commit = new Commit();
        commit.setId(UUID.randomUUID().toString());
        commit.setBranchId(branchId);
        commit.setParentIds(branch.getHeadCommitId() != null ?
                Collections.singletonList(branch.getHeadCommitId()) : Collections.emptyList());
        commit.setNodeType(nodeType);
        commit.setNodeId(nodeId);
        commit.setChangeType(changeType);
        commit.setContentBefore(contentBefore);
        commit.setContentAfter(contentAfter);
        commit.setMessage(message);
        commit.setAuthor(author);
        commit.setCreatedAt(LocalDateTime.now());

        commits.put(commit.getId(), commit);

        VersionSnapshot snapshot = new VersionSnapshot();
        snapshot.setId(UUID.randomUUID().toString());
        snapshot.setNodeType(nodeType);
        snapshot.setNodeId(nodeId);
        snapshot.setContent(contentAfter);
        snapshot.setCommitId(commit.getId());
        snapshot.setCreatedAt(LocalDateTime.now());
        snapshots.put(snapshot.getId(), snapshot);

        branch.setHeadCommitId(commit.getId());
        if (branch.getRootCommitId() == null) {
            branch.setRootCommitId(commit.getId());
        }

        return commit;
    }

    public MergeResult merge(String sourceBranchId, String targetBranchId) {
        Branch sourceBranch = branches.get(sourceBranchId);
        Branch targetBranch = branches.get(targetBranchId);

        if (sourceBranch == null || targetBranch == null) {
            throw new IllegalArgumentException("Branch not found");
        }

        String commonAncestor = findCommonAncestor(sourceBranch, targetBranch);

        List<Diff> sourceDiffs = computeDiff(sourceBranch.getRootCommitId(), sourceBranch.getHeadCommitId());
        List<Diff> targetDiffs = computeDiff(targetBranch.getRootCommitId(), targetBranch.getHeadCommitId());

        List<Conflict> conflicts = detectConflicts(sourceDiffs, targetDiffs);

        if (!conflicts.isEmpty()) {
            MergeResult result = new MergeResult();
            result.setHasConflicts(true);
            result.setConflicts(conflicts);
            result.setRequiresManualResolution(true);
            return result;
        }

        for (Diff diff : sourceDiffs) {
            if (diff.getChangeType() == Diff.ChangeType.ADDED) {
                commit(targetBranchId, diff.getNodeType(), diff.getNodeId(),
                        Commit.ChangeType.CREATE, null, diff.getContentAfter(),
                        "Merge from branch: " + sourceBranch.getName(), Commit.Author.USER);
            } else if (diff.getChangeType() == Diff.ChangeType.MODIFIED) {
                commit(targetBranchId, diff.getNodeType(), diff.getNodeId(),
                        Commit.ChangeType.UPDATE, diff.getContentBefore(), diff.getContentAfter(),
                        "Merge from branch: " + sourceBranch.getName(), Commit.Author.USER);
            }
        }

        sourceBranch.setStatus(Branch.BranchStatus.MERGED);
        sourceBranch.setMergedAt(LocalDateTime.now());

        MergeResult result = new MergeResult();
        result.setHasConflicts(false);
        result.setSourceBranch(sourceBranch);
        result.setTargetBranch(targetBranch);
        return result;
    }

    public String findCommonAncestor(Branch branch1, Branch branch2) {
        Set<String> ancestors1 = new HashSet<>();
        String current = branch1.getRootCommitId();

        while (current != null) {
            ancestors1.add(current);
            Commit commit = commits.get(current);
            current = commit != null && !commit.getParentIds().isEmpty() ?
                    commit.getParentIds().get(0) : null;
        }

        current = branch2.getRootCommitId();
        while (current != null) {
            if (ancestors1.contains(current)) {
                return current;
            }
            Commit commit = commits.get(current);
            current = commit != null && !commit.getParentIds().isEmpty() ?
                    commit.getParentIds().get(0) : null;
        }

        return null;
    }

    public List<Diff> computeDiff(String fromCommitId, String toCommitId) {
        List<Diff> diffs = new ArrayList<>();

        if (fromCommitId == null || fromCommitId.equals(toCommitId)) {
            return diffs;
        }

        List<String> sourcePath = buildCommitPath(fromCommitId, toCommitId);

        for (String commitId : sourcePath) {
            Commit commit = commits.get(commitId);
            if (commit == null) continue;

            Diff diff = new Diff();
            diff.setNodeId(commit.getNodeId());
            diff.setNodeType(commit.getNodeType());
            diff.setContentBefore(commit.getContentBefore());
            diff.setContentAfter(commit.getContentAfter());

            switch (commit.getChangeType()) {
                case CREATE:
                    diff.setChangeType(Diff.ChangeType.ADDED);
                    break;
                case UPDATE:
                    diff.setChangeType(Diff.ChangeType.MODIFIED);
                    break;
                case DELETE:
                    diff.setChangeType(Diff.ChangeType.DELETED);
                    break;
            }

            diff.setHashBefore(hashContent(commit.getContentBefore()));
            diff.setHashAfter(hashContent(commit.getContentAfter()));

            diffs.add(diff);
        }

        return diffs;
    }

    private List<String> buildCommitPath(String fromCommitId, String toCommitId) {
        List<String> path = new ArrayList<>();
        Set<String> visited = new HashSet<>();
        buildPathRecursive(fromCommitId, toCommitId, path, visited);
        return path;
    }

    private boolean buildPathRecursive(String current, String target, List<String> path, Set<String> visited) {
        if (current == null || visited.contains(current)) {
            return false;
        }

        visited.add(current);

        if (current.equals(target)) {
            return true;
        }

        Commit commit = commits.get(current);
        if (commit == null) {
            return false;
        }

        for (String parentId : commit.getParentIds()) {
            if (buildPathRecursive(parentId, target, path, visited)) {
                path.add(current);
                return true;
            }
        }

        return false;
    }

    private String hashContent(String content) {
        if (content == null) return "0";
        return String.valueOf(content.hashCode());
    }

    private List<Conflict> detectConflicts(List<Diff> sourceDiffs, List<Diff> targetDiffs) {
        List<Conflict> conflicts = new ArrayList<>();

        Map<String, Diff> targetDiffMap = targetDiffs.stream()
                .collect(Collectors.toMap(Diff::getNodeId, d -> d));

        for (Diff sourceDiff : sourceDiffs) {
            Diff targetDiff = targetDiffMap.get(sourceDiff.getNodeId());

            if (targetDiff != null) {
                boolean sourceModified = sourceDiff.getChangeType() == Diff.ChangeType.MODIFIED;
                boolean targetModified = targetDiff.getChangeType() == Diff.ChangeType.MODIFIED;

                if (sourceModified && targetModified &&
                        !sourceDiff.getContentAfter().equals(targetDiff.getContentAfter())) {
                    Conflict conflict = new Conflict();
                    conflict.setNodeId(sourceDiff.getNodeId());
                    conflict.setNodeType(sourceDiff.getNodeType());
                    conflict.setSourceValue(sourceDiff.getContentAfter());
                    conflict.setTargetValue(targetDiff.getContentAfter());
                    conflict.setResolution(Conflict.Resolution.MANUAL);
                    conflicts.add(conflict);
                }
            }
        }

        return conflicts;
    }

    public Branch getBranch(String branchId) {
        return branches.get(branchId);
    }

    public List<Branch> getBranchesByNovel(String novelId) {
        return branches.values().stream()
                .filter(b -> b.getNovelId().equals(novelId))
                .collect(Collectors.toList());
    }

    public List<Commit> getCommits(String branchId) {
        return commits.values().stream()
                .filter(c -> c.getBranchId().equals(branchId))
                .sorted(Comparator.comparing(Commit::getCreatedAt))
                .collect(Collectors.toList());
    }

    @Data
    public static class MergeResult {
        private boolean hasConflicts;
        private List<Conflict> conflicts;
        private boolean requiresManualResolution;
        private Branch sourceBranch;
        private Branch targetBranch;
    }
}