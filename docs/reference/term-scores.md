# Term Scores

The score of a term is a rough measure (in percent) how well you know a term. It is displayed in "My Terms", and it is used in tests to decide which terms are tested next.  

The score is calculated as follows:  

![Image](../img/score1full.png)  

Terms with status 1 are tested either today (if not created today) or tomorrow (if created today, or a test failed today). Terms set to status 2 should be retested after 2 days. Terms set to status 3 should be retested after 9 days. Terms set to status 4 should be retested after 27 days. Terms set to status 5 should be retested after 72 days.  

Example 1: Five terms were tested today; they are now in status 1, 2, 3, 4, and 5. The term with status 1 is still unknown (failed the test, so the score is still 0 %). The term with status 5 is well known (score: 100 %).  

![Image](../img/score2.png)  

Example 2: Five terms were not tested for some time; they are in status 1, 2, 3, 4, and 5. All of them have a score of 0, because the number of days indicate that you may have forgotten them. Therefore all should be retested today.  

![Image](../img/score3.png)
