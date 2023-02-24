<?php

namespace App\Controller;

use App\Entity\Book;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;


class BookController extends AbstractController
{
    #[Route('/api/books', name: 'book', methods:['GET'])]
    public function getBookList(BookRepository $bookRepository,SerializerInterface $serializer): JsonResponse
    {
        $bookList = $bookRepository->findAll();
        $jsonBookList = $serializer->serialize($bookList, 'json',['groups' => 'getBooks']);
        return new JsonResponse([$jsonBookList, Response::HTTP_OK, [], true]);
    }
    #[Route('/api/books/{id}', name:'detailBook',methods:['GET'])]
    public function getDetailBook(SerializerInterface $serializer, Book $book)
    {
            $jsonBookList = $serializer->serialize($book,'json');
            return new JsonResponse($jsonBookList,Response ::HTTP_OK,[],true);
    }
    #[Route('/api/books/{id}',name:'deleteBook',methods:['DELETE'])]
    public function deleteBook(Book $book,EntityManagerInterface $em)
    {
        $em->remove($book);
        $em->flush();

        return new JsonResponse(null,Response::HTTP_NO_CONTENT);
    }
    #[Route('/api/books',name:'createBook',methods:['POST'])]
    public function createBook(Request $request,SerializerInterface $serializer,
    EntityManagerInterface $em,UrlGeneratorInterface $urlGenerator,AuthorRepository $authorRepository): JsonResponse
    {
        $book = $serializer->deserialize($request->getContent(),Book::class,'json');
        $content = $request->toArray();
        $idAuthor = $content['idAuthor'] ?? -1;
        $book->setAuthor($authorRepository->find($idAuthor));

        $em->persist($book);
        $em->flush();

        $jsonBook = $serializer->serialize($book,'json',['groups' => 'getBooks']);
        $location = $urlGenerator->generate('detailBook',['id' => $book->getId()],UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonBook,Response::HTTP_CREATED,["Location" => $location],true);
    }
    #[Route('/api/books/{id}',name:"updateBook",methods:['PUT'])]
    public function updateBook(Request $request,SerializerInterface $serializer, Book $currentBook,
    EntityManagerInterface $em,AuthorRepository $authorRepository): JsonResponse
    {
        $updatedBook = $serializer->deserialize($request->getContent(),Book::class,'json',[AbstractNormalizer::OBJECT_TO_POPULATE =>$currentBook]);
        $content = $request->toArray();
        $idAuthor = $content['idAuthor'] ?? -1;
        $updatedBook->setAuthor($authorRepository->find($idAuthor));
        $em->persist($updatedBook);
        $em->flush();
        return new JsonResponse(null,JsonResponse::HTTP_NO_CONTENT);

    }
}
