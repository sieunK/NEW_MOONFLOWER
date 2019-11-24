#include <stdio.h>

int main()
{
    int n = 10;
    int i, j;
    int a[10] = {9, 3, 2, 4, 5, 7, 1, 6, 8, 0};

    for(i = 0; i < n - 1; i++)
        for(j = 0; j < n - i - 1; j++)
            if(a[j] > a[j + 1])
                {
                    int t = a[j];
                    a[j] = a[j + 1];
                    a[j + 1] = t;
                }
    printf("1");
}