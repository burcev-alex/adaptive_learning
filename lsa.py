#!/usr/bin/env python
# -*- coding: utf-8 -*-
import sys
from tkinter.filedialog import *
from tkinter.messagebox import *
import numpy
import numpy as np
from numpy import *
from sklearn.cluster import KMeans
import nltk
import scipy
from settings import docs,stem,stopwords
ddd=len(docs)
from nltk.stem import SnowballStemmer
stemmer = SnowballStemmer(stem)
doc=[w for w in docs]
def STart():
    print('Исходные документы\n\n')
    for k, v in enumerate(docs):
           print('Док--%u | Текст-%s \n\n'%(k,v)) 
    t=" "
    word=nltk.word_tokenize((' ').join(doc))
    stopword=[stemmer.stem(w).lower() for w in stopwords]
    return WordStopDoc(t,stopword)
def word_1():
     txt2.delete(1.0, END)
     word=nltk.word_tokenize((' ').join(doc))
     n=[stemmer.stem(w).lower() for w in word if len(w) >1 and w.isalpha()]
     stopword=[stemmer.stem(w).lower() for w in stopwords]
     fdist=nltk.FreqDist(n)
     t=fdist.hapaxes()
     return WordStopDoc(t,stopword)
def WordStopDoc(t,stopword):
    d={}
    c=[]
    p={}
    for i in range(0,len(doc)):
        word=nltk.word_tokenize(doc[i])
        word_stem=[stemmer.stem(w).lower()  for w in word if len(w)>1 and  w.isalpha()]
        word_stop=[ w for w in word_stem if w not in stopword]
        words=[ w for w in word_stop if w not in t]
        p[i]=[w for w in words]
        for w in words:
               if w not in c:
                    c.append(w)
                    d[w]= [i]
               elif w in c:
                    d[w]= d[w]+[i]
    return Create_Matrix(d,c,p)
def Create_Matrix(d,c,p):
    a=len(c)
    b=len(doc)
    A = numpy.zeros([a,b])
    c.sort()
    for i, k in enumerate(c):
        for j in d[k]:
            A[i,j] += 1
    return Analitik_Matrix(A,c,p) 
def Analitik_Matrix(A,c,p):
    wdoc = sum(A, axis=0)
    pp=[]
    q=-1
    for w in wdoc:
        q=q+1
        if w==0:
            pp.append(q)
    if len(pp)!=0:
        for k in pp:
            doc.pop(k)
        word_1()  
    elif len(pp)==0:
        rows, cols = A.shape
        nn=[]
        for i, row in enumerate(A):
            st=(c[i], row)
            stt=sum(row)
            nn.append(stt)
        return TF_IDF(A,c,p)
def TF_IDF(A,c,p):
     wpd = sum(A, axis=0)
     dpw= sum(asarray(A > 0,'i'), axis=1)
     rows, cols = A.shape
     for i in range(rows):
         for j in range(cols):
             m=float(A[i,j])/wpd[j]
             n=log(float(cols) /dpw[i])
             A[i,j] =round(n*m,2)
     gg=[]
     for i, row in enumerate(A):
         st=(c[i], row)
         stt=sum(row)
         gg.append(stt)
     l=gg.index(max(gg))
     return U_S_Vt(A,c,p,l)
def U_S_Vt(A,c,p,l):
    U, S,Vt = numpy.linalg.svd(A)
    rows, cols = U.shape
    for j in range(0,cols):
        for i  in range(0,rows):
            U[i,j]=round(U[i,j],4)   
    for i, row in enumerate(U):
        st=(c[i], row[0:2])
    kt=l
    wordd=c[l]
    res1=-1*U[:,0:1]
    wx=res1[kt]
    res2=-1*U[:,1:2]
    wy=res2[kt]
    Z=np.diag(S)
    rows, cols = Vt.shape
    for j in range(0,cols):
        for i  in range(0,rows):
            Vt[i,j]=round(Vt[i,j],4)
    st=(-1*Vt[0:2, :])
    res3=(-1*Vt[0:1, :])
    res4=(-1*Vt[1:2, :])
    X=numpy.dot(U[:,0:2],Z[0:2,0:2])
    Y=numpy.dot(X,Vt[0:2,:] )
    rows, cols =Y.shape      
    return Word_Distance_Document(res1,wx,res2,wy,res3,res4,Vt,p,c,Z,U)
def  Word_Distance_Document(res1,wx,res2,wy,res3,res4,Vt,p,c,Z,U):
    xx, yy = -1 * Vt[0:2, :]
    Q= np.matrix(U)
    UU = Q.T
    rows, cols = UU.shape
    a=cols
    b=cols
    B = numpy.zeros([a,b])
    for i in range(0,cols):
        for j in range(0,cols):
            xxi, yyi = -1 * UU[0:2, i]
            xxi1, yyi1 = -1 * UU[0:2, j]     
            param3 = float(xxi*xxi1+yyi*yyi1)
            param4 = float(sqrt((xxi*xxi+yyi*yyi)*(xxi1*xxi1+yyi1*yyi1)))
            if param4 != 0 : 
                B[i,j]=round(param3/param4,6)
            else:
                B[i,j] = 0
    arts = []
    print('Результаты анализа: Всего документов:%u. Осталось документов после исключения не связанных:%u\n'%(ddd,len(doc)))
    if ddd>len(doc):
        print(" Оставшиеся документы после исключения не связанных:")
        print('\n')     
        for k, v in enumerate(doc):
            ww='Док.№ - %i. Text -%s'%(k,v)
            print(ww)
            print('\n')
    for k in range(0,len(doc)):
        ax, ay = xx[k], yy[k]
        dx, dy = float(wx - ax), float(wy - ay)
        dist=float(sqrt(dx * dx + dy * dy))
        arts.append((k,p[k],round(dist,3)))
    q=(sorted(arts,key = lambda a: a[2]))
    dd=[]
    ddm=[]
    aa=[]
    bb=[]
    for i in range(1,len(doc)):
        cos1=q[i][2]
        cos2=q[i-1][2]
        qq=round(float(cos1-cos2),3)
        tt=[(q[i-1])[0],(q[i])[0]]
        dd.append(tt)
        ddm.append(qq)
    for w in range(0,len(dd)):
        i=ddm.index(min(ddm))
        aa.append(dd[i])
        bb.append(ddm[i])
        del dd[i]
        del ddm[i]

    resultActial = 0
    for i in range(0,len(aa)):
        if len([w for w in p[aa[i][0]]if w in p[aa[i][1]]])!=0:
            zz=[w for w in p[aa[i][0]]if w in p[aa[i][1]]]
        else:
            zz=['нет общих слов']
        cs=[]
        for w in zz:
               if w not in cs:
                    cs.append(w)
        sc="Евклидова мера расстояния "
        tr ='№ Док %s- %s-%s -Общие слова -%s'%(aa[i],bb[i],sc,cs)
        if (float(bb[i]) >= 0.3) & (float((len(cs)/len(doc[0].split())*100)) > 30):
            resultActial = 1
        else:
            resultActial = 0

        print(tr)
        print("------ \n")

    if resultActial == 1:
        out_green("Базовый текст рекомендованный к рассмотрению")
    else:
        out_red("Базовый текст нельзя рекоммендовать к изучению")

    return resultActial
def out_green(text):
    print("\033[32m {}" .format(text))
    print("\033[0m")
def out_red(text):
    print("\033[31m {}" .format(text))
    print("\033[0m")
def out_yellow(text):
    print("\033[33m {}" .format(text))
    print("\033[0m")
def out_blue(text):
    print("\033[34m {}" .format(text))
    print("\033[0m")
STart()