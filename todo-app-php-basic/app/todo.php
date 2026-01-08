<?php

function getTodos(PDO $pdo)
{
    $stmt = $pdo->query("SELECT * FROM todos ORDER BY id DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addTodo(PDO $pdo, string $title)
{
    $stmt = $pdo->prepare("INSERT INTO todos (title) VALUES (?)");
    $stmt->execute([$title]);
}

function markDone(PDO $pdo, int $id)
{
    $stmt = $pdo->prepare("UPDATE todos SET is_done = 1 WHERE id = ?");
    $stmt->execute([$id]);
}

function deleteTodo(PDO $pdo, int $id)
{
    $stmt = $pdo->prepare("DELETE FROM todos WHERE id = ?");
    $stmt->execute([$id]);
}
