<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

class CommercialCatalogSeeder extends Seeder
{
    private const DATA = 'H4sIAEy9dGoC/9Vdy3LjyJX9FYSiF3ZEW0a+8OhaySp2Sd16jYpV44mZiQkUiVKhTRI0QMpdNeGP8Cd4OYteTHg3Ed64fmwSICUlyUNmJsRM0atWlyjpXODee24m8hz8+38fDcphfvTd0Xm/d/mbMAzJ0bdHk2zc/NPJaJwN8kn2Ux4M8+CmyL9kdZAFoywoJrN8PM2rIpefHpWTu6PvJvPR6NujaVUM5I+G3x7dVeV8Kn/Jdf+sdys/NZ8UM+V/J+Usrx9+qi7n1SD/r6r809F37M/fbmCiKqY/zou8agDdVfOs+e+0rGbFoAy4CINZOamD3wQ3ZRWM5e+3BHd5fdU/2wWOA3BsDdxogW5Qyms0yYcSSfmxGBSTFisNg2nhAJgAwLgNMO4IWASACQysJ7+cls1Xs7yqZOZVRWaL583F9e9OLnYBigGgCANqE2yWj/J6UE6LQXudmGhyLPhUVllQVkN59UxQnt6eXPXePsE8u363swgSADLGIMfFREKr7jJ5Q/Mm96druX9E4ij4VNXB+Osvk2IsS7gMWBgMv/4ivxxko3wylDGUwagM/jjPg3Iwlxc/kDGM86o8eoyGHD8jC1IQT7IlHpmeWRtQG4lYAH2KRv/Zb4O6mASlbE/NNTl+CoGKUA3i++vbHy/Ov+8r9+X1yb/tCoOEII4Ux/Hw91uc2WRUfJxp0+Ty5GoV0OKDSzxHb6q5rI9i8lFWxrD8Lnj4+Kvg3aQYZsNgKu9lkcvybv7ofTaS/1i9Cm6qfCDv76e8yuWHymCS32Wz4r4MfvUbdhyGvw4mZTWWH/7SfDML5DUKj9biJptxkxDHfa3E3Ze18ymbDOW3tLH3exe9s5Or1xe92203ZOMCrP3MCmQKIBMM+SabydJovjptc0l+ET0wSZNXTVbZNqJ3V+d9BfmOe0AM7wEgRkJBQLVsXNmsrINPxbDK5iNJjfWSetSYlK51lLT9rG57SdsgHhpbrXxWG7KueAB5EpU8J5LFJQHsfaIggByJSo7TuWwjo2zclE7LP9N5U0T1/smHADokCh3+TsKYDJpkfJtX90WTMqfleDrKZ7aXZXfnaD+7s22sZx+gTaLQ5mn2QU6HwefgovzTh/Jzm29B/pMkpGFVTof54A/Lqey+kOOkvhlIvnx7c33bN4zn8fNWMQGWJfF6TKMDBA7olCSbwKOfo2bak8ze3hP55X0+kTk1kd8b5nU+uZPJ9in7MOoSl0G2U0CXJNXifMyYQTbOgg/ZT5kjeIDVaLgd3nSUzbKPTZ92hAdQFiUYzzSTM1qVj8tRM7PJTMnb4dkRMEA9lFoBk0XUr7JJ3azV8uB82LSK2/xOTqzl8WEUFQX0RNlmkH6vPKAuyjdByeL+lNVFbbPE7AYIEBgVuwFxp4AAMdEIAXpdjkafF7dv9pSKEt/74i7rhO772975mzN1zKMyM+6KelbJkavJi/tmeSW/1fSPumzSbljW64kHaIjGKIKP8+quWXtm9SwLeNL5qm7gBrswoHXTBIF6KXJhqHun3RAGIpwdRhdigANY2DGq/OdZ/qEuOt4CkywBxMDIM9BOWrQBCQ/mfgBWYHRHhL6GFwaYgbEdwNrLO2gv7wPGA7nEgFIY3xFJs679+td2YRuMy+F81KZPM6HdFZVcPc/kdx2xDQNswyD9PQ2LNpsIAJRmVc0Ae7BIi+hxRXMg0xcDSxoWa8NQiLxoZ8pqMVPuh823RrYGngOyZIkG/HJ5fHCLSw54laWaYNihBgPolIeaYJ6I6GmHTP/BfXEqB5zKiQZy02DqfJxNOrWYf+31ftwJCZAgp7shNZN/vRj+nz2v6vsyB2zImQ7hApgyd0iwWRCFLgdrDtiOc2ukUYNUjklOoQKy4ytkN5aDXECFuq9sVvxrG6f61vvwAyv4APXxaANfsh1e+5Gvf1t+Jh9lw+WO98PHPO+ycsCCXGXBk8vz66vgdS94f33xL+961/hC73pUZ3DfBSqmZOO6NivncjTLS/BEYX/zjUD1ku4E8zQUOCoMAQpDhBuY5CzSPgi7Ob85sbhGdk9VBCgCQXRY+puX6Ej+Wz1qHj+tTFKy9QwK2XpGwbQqP+eDWblHrhMg3wVdz/de/+QJ5fve2fnpu4vr4PLk9vQkkH/y7elZL7i8ft1r//H05Co4+fqX64AutsQ9V3AERkHBVm9H03BW95OVJY3j5YJA+HgHfO0Dw8MY8gSYWIXoENO4G5XqD+eAKVREHQB2nawNJigBxk4Rb2KUd2FBqI5ggFFTrHDP8gF92+Tb69S0tXbx35zWKR+eN6xsEmSTeTba/9PcCCXeCjdVzUbw3bw9atPuPDdHiUazNoLFuZW6/FDlw2LcDHblRA4gDmCC9ItUusqrWfFRZtlgyaTjTF7DHQebds0XBnBApkVkB5xpXtXNhuHzwRiNlRFIwUglpXk9K4dFGZy0yfes5/IWxAKGskghltf5tKyLWdn2iTuZapOZesKjWZdX8i+0mTefyDycNtd3kZTZyjnPQTmeymF4eQBiGU8kwvD4GWcwIjDGRVyFXy/qoankH7LBH4K38irkzziAoyHCCMxwkTAB9NyTGSYlAoa6KALgYCP02QHBABfFGGh/A6isxuxX9a9l014e/Tx7PLFkfaLS4OAnGH0ihVpe996evutd9a+boTI4ueqfn57fXL/ssZsYMEyUrlzf9gzkonIX51XLqsodHJsFJBKHK0jk6PJFOUT1mJjLW+7iPFUMuCRWuKQ3yj7IQfWBSV7PB5kLFIAxYqqiyO8fjkS2A8zeT1nHgB1ihR16k5ns/srNeTilaX9TVg82Aiig08dKp+/Vs3nDnhJENZd8tPZA3hOdxqD9x2ITZb+clndV9tGkJdleKNDkY6XJ934eZG3aZEDQ8HSou1DPdBdWR7qfk3Cg8ccxBM9oKCeM5eFUCz7vndz2zy6v359fvTFm9QT0+DgxhdWsabvB0p7nB308Tk1xmaxLISztbUxAV09CU1yGTxwgNN0zhwR09kTt7D837WLR2fu5nFirbBT8NrjNi/HTN4b5R1kes+I+W9nmGhfNkZzm6NbkXlZJs9clWXNSrpzmev654wTQQqLQwvdyeMyDS1mps2JcPnvPHAAArJCwdQBtA9Yt+Z691ZcAWkgUWvh+cbbpcXvhuTseJpAAByQKB7zJ6tni6PzNXK5JHcz5CaCARKGAN1Uxau5RC4L8x1Hwc5Aci9li96BZwEm2e9kBNQE8kMRqBHIkTR/uqPZx/dMmdLM+GDw+C1ucKt/jznMKiCJJ1nCfNLrCefNUaId44kUuewr4JElN4ONd/heOBrBQGm6PJjqmz5Tn7DsAQFYpMQ7AaO5wGwCgqpQaBfDs7ZA1pZTJFl0KyCRlRnAPMv8BEaV8eziJXsj1KPU0U3Kt76ZoxuwU8FYqDAGvLGOAEPVV0K5tdsYhf8cri9XNenh6vSqgtTQyDNBwLF4HpZuISQg4K40NQNmXqP3uGgkBJaWJAbqOFbkO0YD0SYiIZp029yfwcCH9AhJkEq5z5c1zlEpOUBOAep0gVaV90g4szzriuZYgu8NafNgqJnACm4RUG5PNDOk8hAiEwHaE0CxDSGg0uqw8l9t3IK+CLkobAp4uk3CdaPtr8cZh0H/WbdOI2B8/vSqdRLdGWEFdIdoNX4vl59t7ufz0tyrzbkrB0Rbjt5iFj7vQsOmVicGViYyvTP9A+wkNExBXvDsuyh7CajaIX79AS+lWiZQjGktMg8Ujw/E/S/AM3el0d/DpM5vQvtOVgUeThIS7gygPvQgZeK5BCNkS1WN3Wd0dMlrOuw4EPAkhZH066TfPiiXqs7bl936emZzp6zB4swQlC9uCBhsYrbDS4xrwgZFOleXjBWKkV8E3nBxz/niHmtP0VaZaOD0cdWGcHifRM1aPDDxWIYTDcH/I6jL4QbTnsfa8u8zAMxRCVmaIr/+3bZ+k4ZP3Hc7y73trBAzZJFoPYZCPyvrrL82IbXQ00a1VSogue6zBvF8xm4s1XAiGZ5JshFVOgt9V2Zdy2SW//m3aHED6rKxJNSW+06XMat70dF1Qiqbr16V5ZPNwxxt/qTDctidm2nfXp+dOKyK0zKChOXg/W0tgXqLEHKTPHSYwP1C6DnVjddKMRce7Rzuxal5nuWNLITC2Dmz2VLMLednswOa1V4HokubADY5Qvjt60S14GobHHrdwgDEXoWJnZLTjbU1F6HZPJ+50a1FziHZeAHaIec27BA/8gQiNdwf/mNcGEQu/yUxRk0oMw7G7l8J1MnfqU8B2h9B05wVo+CO0fhxuSR8EGF8QFtoB67Iu1p6sI2gzhRE7ZHsYvxAykM2M2iFz9SwR7b8xZo7Ny9wHrCgI4+Yg9/XoyAgrmDCY0GN1X7rAUIKwyBKZm9oFxhKExZbQ3BQvcJQgLLGE5qx6waqNpRbg/JQv4AweWqD0Wr+ARrieRjxQLzCFIJxaInNTJEBNTjizhOaqSIAsnHBuAc5LkQBtOOHCAqXPIgEiccL1VEKd1whwCSE8tgPmhuIEaoKJHTJHxYs6XmqHzFntgp4nQnNsXkoXeH0QQcxB+qxcYFVAhJ5EqHt6A+4ERDBLZG4qBDgVEMEtobkqEWBTQISwAOenRsAYLSILlF6LBLCI0LMI81AkgEVEYonMUZEAGhGpJTRnRQJ4JAotwO0t/UweHgGHAhLpCYU7Tz901ieidsDcjFfAloBEzA6Zm7oA1gQk4nbIXJUFMCwgkTDH5oU6gKcBiSJzkD6ZA5gekEjPHNw9cwCjAxIllsgcVQhgjii1hOasRABzxKEFOC81gk75xcQCpc8iQYcAYz2LCOc1go7rxcwOmBt6Qyf4Ym6HzE3xAnMDEgs7ZK5qF/gekDgyx+andAFlxLE5SK+VC0gk1pOIcE9vwEGBxKklskM4sE2AkwJJQstI3NQ6MEsgCbGE5qrYgRUCSagFOC/VDiwOSMIsUPosd2BnQBI960Tby73rG0a7rKKBlwFJhCX61cPJKV3C35ShH+2pxABlJZEl6L2UWLdzxMCRgSSxBX53Gy0dAwLEkugpL973O3atz/miXp3iA8jbcB8CH1LgJ0DS0O4GOKFDClwESErMkfkgHIpEKCk1B9k3d2Ts0KQpEpOkzByez31ZihQeqZ4PE9fTL0VmEKmwA+Zk4UqR4UMa2SFzU71ICpDGdsgczbKUoKJNzLF56SwElW5qDtLjJEvBq99pqCeRJHRfuhFARiyROaqQGECjltCclUgCwDELcH5qJAUouQVKn0UCXtNOQy2LuD8aQsEL2mkY2QFzQ2/gVe00jO2QuSle8K52GiZ2yFzVLnjFOg1Tc2xeShdIqigJzUF6rVxAIoTosXooXcAhhFoiM3vHlH3xAgohzBKbo+oFvEG4JTRX5QvEaZQIC3Be6he8F56SyAKlzwIGr3unRM8j1PkzUQpe7U5JYonMTZGAd7JTklpCc1YkgD5oaAHOT5EA4qDEAqXXIgFcQvVcwoJ//N15mQDZH6XMGpubKRUo/yjl1uDcVDHQ/lEqrMG5qmOO6jiyguelkjmq5NgKp89a5qiW9bTC/V5TMCDqVfgA5PLBTm3zFOhV8A0Jqd6m7DlNC0yZejH/lvhWh3SDAEcywjTZFqDZA1Vt6wPKRKp3BdgSosaM7gn7K+CQusWnvLkIof4uk3C3n4xBowU6SKq3IXi6FI6fBAEBJNU7EXiYgIHYkerdB6j7U4EU2BhTvdeAj9EcvDqe6r0G/EzmQCNK9WYDngdzIBeletuBl5nLgYCU6l0IqPtTgRTIR6neeYB6OEtHgWiU6n0GqJezdBSoRKneaoC6PEvX6fQOBaJSyplFID7rCCg5qd6ogEb7xrr3IzxA/En13gY0dt4fgNCT6n0MaOye2oHQk+qNDKiHU09A50n1RgaryJz1LdTvU3NsXqgdqDup3tOAxi/RkoDgk+qtDWjsfioHgk+qNzJYQ+amQoDik+qdDNaguSoRIPmkei8DBZyfGgGMIYQFSq9FAlhE721A3R8LBJpPqvcxoB6OBQLJJ9X7GFAPxwKB3pPqbQyol2OBQOVJ9S4G1O+xQKDupHrzAvoixwKB4JPq/Qzc+3xQoPekejsD5uHEE9B7Ur2dgQcDEgrUnlRvZuDFf4QCkSfVexgwvycmgLqT6s0L2IuslYHek+rtDJiHE09Av0kjrfCGbZ54On7xdTvQe1K99wHzcUAKqD2p3vCA+TkgBUSeNKYW4LyUO1B30phZoPRZ70DMSfUGCE+m8OqbrYKbvKrLSTbaPzsC+SbVuyEw98tloMukeisE5uEhFnjpNdX7HzAP63gg/aR6twPmZRkPXkxN9X4HzOsqngENKtVbGbCXWMQzIDOlem8D5v4hFgMyU6o3NmAeHmIxIC6lejcD5uUhFgPSUppwC3B+agQwRiIsUHotEsAiencC7qFGAInobQe4+/UyA+8so3r/AO6jeAGFJKkdMle1CwTNVC/692u6xoC2mer1/y/iucaA2pnqbQC4+/UyA1pnqncA4B4WmQwonKle789DPyUCiEOv+eeh5xoBjJFGFii9FglgEb0lAH/2UsTkzclI76y3BODPn6U3X55qhhewi94dwMM4DVTOTG8FIDyMCkDmzPRWAD7mfCBzZnonAD9jPhA6M70RgOcpHyiemd4H4GWGfCB8ZnobgMhD5UYAWGQHzFHlxgBZbIfMUeUmAFlih8xZ5aYAW2qOzUvlArEz09sARC9SuUDyzPQ2AJHz3WcG5M1M7wIQud99ZkDezPQeAJH73WcG1M1MbwEQ+dh9ZkDczPQOAJHf3WegbWZ6A4DoRXafgbSZ6fX/sfvCBfShV//HHuoWcIde++/+eDQDL9Rlet2/j9PRDLxGl+nF/l4PRzOgo2d6jb/7s9Gd5CcMCO+Z3hUgdj9KA1U70zsCxB5GaaBjZ3o7gNjDKA0k60zvBBB7GaWBUp3p1f+x31EayM2ZXvMfv8goLVAb1/OL+1EaqLiZXrMfexilgYCb6aX2HgQZDCi4mV757kWPwYCGm+ll737lGAzIuZleAv8iagwG5N1ML4pPtpv+78fn4WhPqQxISC+sT/xmCyAjvcDevdcuA3JwphfVJx6mHqAGZ3oJvQcTYAak4EwvoffiAcyAupvpNfR+LYAZeHkw02vpX8QBmAEFOtPL5RP3Uw9QkDO9ND7xMPUACTnTa9sTD1MPkJAzvbg98TL1ABE508vbE79TD1CTM73SPXmRqQeIyple+Z66z0Kg02Z6tXvqfhgAKm2ml7anHoYBINFmeml76mEYALJsple2p16GAaDCZnphe+p3GACKbKbXtacvMgwAjTZTZe0/nvfbI0bBNJsVk7yWGKvZ/C6zqtrr/lnv1rxoE1QasTkmk7JYg6SvigRVRWIOyrAi1nBpCyJBBZGawbIvhjV0BvkF5KZMVbP/WMwafMO8no6yL9m4USuWwadiWH3963xUDEqXeQZyXxWxm2Jzk29gLlFV66bgHOUdUI0yVbtuAs9H/gEJKVOV7Bf5fTaZPeCTiE+zDyP7+2mCBMxIqnL9ovzTh/Jza5MQ5D/JSn1dldNhPviDvFI3a+8XtDjGqSsCIBBlqmj9CRbzCgtwgCpT18G6L7Kf8g7AdsurHz9vo7BmQC/KVBn7Yyx1PrnLJoNPTQoeYhyAa1TRO45jeX+aNx4tD3w/O192xXNT5U1L+ZRXefO0Npjkd5L57svgV78hx2H462BSVmMZ35fmm1kQyn97FXR67gs0rUwVzusuh+h+NUzaDeAPVTt/yRoMl9lMXsJsJHvxKB/IDjjNqkxGPZ5mg1kmL+TEthO+uzrfeWacA70oU3Xzl9kkuCg+zgJCJbO2t0QCzUazuURmMQxcnlxdnH/fN18scqAOZapW3gCZ0VpxE5lutciBPJSpyngDaCZTyiYy7aDCgTyUqWp4A2iGM8omOt2YwoE8lKmKeA04+wllE6O+VjmQhzJVHf+Ekm8ticJJRYCOr4rjn4AlvmsVNd/UCpmzWgXLClUQbwDNWa0CUlD17wbQ3NUqkIOyhBqD81SrQA/KEkQSNPRcEkD9yRLEEdSII/b47gMeHSeh1UsuuiQ30JiyBBERjUy6aNd3uHe4cYCkksgKuNnL8zo0MyBAZUlshW0zqarHi+r9bAUHWlWWJFYBrbXAI67JlMXnXx3tq08C6ktWqW/0MNLMVtHfdJ0Udi8nHz5us5rkQOvK0tAojGVWHR9GHIBTU2IcR1c+dRAJEs2m1CiS/pZHFodxi5DmNmUgML6jh3UZyVzEAlg+5TiWWb3j1hxGNICzU7E1mkPtZUj5m0bGYezqAeTYgi22RrMBGNB6GhsBfpyzB53nbBd3ANB6mhgFdOC9C7B9ithe6Gny6IeymmQyRlk3cjpZvOVzObv83E5icmWijmLHAQ3kL5ssOiIhi88eH73YxdicGXiIZgYxNrrFh3GHUxAUGiCSvXf0TZMP4w4CJNM8pFtRr7e81zLTjh0sgoE6mofMGNZbZ7sbQB3NQ24GrOOGkINcBUpqHgK+ftr8OPDiAwJsHkY4oMMfp4Bkm4fx1miMN5F3DyEuAolAIIlxIN22dlfjsEEbA7SpEdqm6Vw62u0Fum5OQmNYDnshYDwCGA9vVCoVeH5wFQiU4ZxQHNpsT49xXIQBaJQAGu24lbrf/VMgJ+eE24B9ezibMEBNzokwDaapjYMtDUC1JMKR7WkCWm+SxtMtkM5z1apjvMTK9vu43cVVB0xKAJMy+s8w4AB5Pifp1mie+XynVXpk4yALxHTcnAUKFivgxe8dz/PqS7mvHga4moZGgbnlaqDn5xRwNSeHtDoFyn5O6VbUh7qvCHwAOGXGYZztYQzeTxyAoSk3juNQeimwGOBUGIexp6MT5kUACJeuEm575Ln80Dxlzdq+Vg+y6s76uPjqY+QNgA+fXkUHKFZ1M7ksJ7MsaOHUwaPczq678DA89n1MlgNvAq46oLSBLeMiYbfAotXAvr++/dEm+x8/bxcYIF/VLmUlMNotMP9BAeJVrVbUoGhAfkuD/u64jg22UDyFBqhb9WpZCe14Vd9tQIB+ggCmCVy1ddkShMVQ5CkOQOWqBcyWOA68eoA5A1c9Y9ai6lsQuqcAAKWrRjJqAOyf5aYA3lftY1ZicpJqJubxHNg9cBZjmNwLyE7+fxyYQ3C2he87ehhqyEMvuogQy22h7uZJb/CPv1tr8wEu3aoXmC9wHhrDMtrmB7C0px6B0QLnxBhXt/ZmsKMfAxLh1BhX3ZUQ9dsEwAGCc2aEzP7gNgBocHIbeEFwzrdDDH1UALCB4FwYgnKX/6P188gQlbvsB42Wx4aoXOY+WBfxxACXt8wH3Z/v6P50D5nvYrCJAV2IHXQhfFRwArhCEENQzio4AUwhqCEqZxWcAJYQzBCVwwpOADUIboDLVwUDSxQuttME9ZL4gCREZIbJXd4DjhCxGSh3aQ8IQiRmoFxmPaAFkepheUt60O6jUI9vdtjLc2CdwiOyIyw/azHglMIjagzLWUUD3xQeMWNczooaGKfwiBvjcljXwDuFR8IIma/SBpYoPNrOHcxLAQDqiGIzTO6yH1BHlJiBcpf6gDii1AyUy7wHfBGHeliekl4A5xMekx34vDR+AWxPeEyNYblKfQE8T3jMjHG5yn4BDE94zI1xuSsAAdxOeCyMkHmrAdD44+2Nn3spAND449gMk7vsB40/TsxAuUt90Pjj1AyUy7wHjT8J9bB8JT1wNuHJ9sbvY9tKAB8TnlAzTM6SHliY8ISZgXKW9MBXhCfcDJTDpAeuITwReljekh50+iTS4+tXWT1qzPkcPN/duoA/WgcPKCHZTglicbThoE7OCOATwpNEF0NndY2noAADJdsZKPbSSgH9pKEZJmetFJh58JSYgXLWSoERB0+pGSiHrRR4avCU6WH5aqXAJYOnXI9vb63URSUDzwyebuev5DCfSQrgpMHTSB/GYd8bQH/pOv39lAe/DV7n9Xj5P/LX9Ub5vYypWhhpdzhIblIMgNbSZBObhHBXzbPG8L7KXeAATJSmmzg+L67RDkxHN3lVl5NsFMzywaTxRm8P42fVOKuCUdb+yNGeQG9SlVgx0Chlehb3eZOZVlK2NSgP/7sdCfCPECuuF9f969unS9T+b3BzcnsSvO+dnZ++u7gOLk9uT0+Cm+vbt6dnveDy+nWv/cfTk6vg5OtfrgMatkI3HdjdBdR+1qp4gAWFWHHGmFdlIG950SgHs9G+DZYFcJoQqgXGdXu8sV5ylwOrewFcIoTqdXGTjRoXyEHWNgtFktd+o6d+Ax2AtHvNgwAWD0L1rJB/VHL34lFIf6shdyyOnWVQNwNyAdwehOpdIXNsXNQLberTWL//fIsBjBjDUB7N5hNJF8HbbNTeaxdZmABciQmuN/NsJqfNkQv6YqgDpyaozsrJcG5i82kPCrgWCNUpYjuoq0LONtnd3MWlAiYEQrWJeET14GIv6+UmHxXyL9b2fU2b58BmQKjWDo8s/jk4y6tq8U4ROfX16mk+WKB7ml7rPb/MRgB1vVAdG4zRmVnJ7hQAInigV6keDcbwzJwYbV+3I4BMXqi2C8b4FsvUet8v3BFA+S5IZAnQwxt3BFCyCxJbAsWro2NbuPodQCRNFcCDQ6ieCTej5p1BQfvKjGr5+jYiwuDH9ye7jbTXLAbkp14F31B6HEN37Nak7/hoP/kNaEc1TpAhzbKPzatRlhEc0ItnBFDzC9UcQQHfvGEqa14w1c2q0lM8gNhUT4Wbctjm1En1odzDy6jMkh5o9IVqmXDTLFiCcXmfV7Iily+3GZfD+UiuUpUXe7l5n40A2nuhWiGswvOIC/Cuam1wm8vE/KK8YGw6byd7B50XKOqFak9w2zTaBkLbv/KRCwiARlXLgdu82dpo3ivU4HjT7oW078Esm9dgugAEaFN1GZCA5nk9czAuAmG5UG0AbhvfoKdFsGxXdjuBvZPb/tnl9fvzqzeGzUr5Cbt2BbhD1f1vRmLhtfNEb7KlP2OVD3TiQtXxY5Buhl2g9Raq8P62HJYP79hqdkuXlqh7ePkESgr9pQOdX5XYvx0UdS3/aPtahE2DnCesX3/ZfnefcWdB51eV8wDexhsauNEbGoJvrF5SYjvCA122UMXyK4GQBz+uG0dvGBVAfy1U6fvb/K7ZqWzTLfv6v3Kd3TRr+fOVg24JRNRC1aw/jnKyTQ2ajZDBA4kMJLeWk2MH3BEB7lAF6g+Y2m2RcT7IJh04TL9TDrTbQtWXr8C4XMKQRHKb1x25zeTaAE5QBeVvi7rZQgsuLs8bWG0utYRz0zzaWD7R2G8KAdG2UMXkb+fT5krVxde/tYkzrcrPzTsPHVwdoNMWqn68L2eeT9lkKGdVth9no95F7+zk6vVF79b0yaD6Iy52p4EiXKhadeUaPN/dAcZvsPxfvQjd4gRtVFW+K3F2tNog/u502ukKgN6tCuuVK9BVz4Tvr25ySNCtEduAMV+o0OWKjFAZnRbCqLRDKxApClVdvwOWyXiCUemnFCBSFKq4fgcswxNDGJl2mgPqRKGK6rcAs9+CxfgMWAgIDYUql19FyD2lP9AZCkGMULlMfyAzFIIawXKZ/kBlKAQzguU2/YHIUAiuBeYz/UH3F2I7Qm+0BISGQkSmwJwWAeAAEZsic1oHgAZEYorMcSkAJhCpCTZ/1RAB8aGItpKB8FMKEdAeiogYoXJYBxGQHoqIGsFyWAQRUB6KiBnBcloBERAeiohrgflMf0AGESCDUbsc3YPFq8t12tF6cIBQoggHJzoGt+fF9kYIgHlU8X2/qD6Wy+xZOaR39LqQUdTFrLhvNwfz5gjyoN3j+dycVRosvpbZP89Gx9b70dtO83XZKoiAfFKoYv5+cyy4CaIYzcfFZBmGRRO221mPCOKE1AyPiy3pCIgSharg3wXIsMNZbtpHQJIoVNF+vznB0tz4k+Fc/v2Riye7EZAgClWir9Rycwpf5qPM2YV869Z0B23z6beBiWsEVIhCFemvImsPv7/JJrPqs+UGX4drBjq+KtNvjhh9KKvh4pFCI1ZoD1lKWKO7cu+7+BFQFApVm4/hTOd5NXNydUC/VXX472VtZd7AgL6oCvDfPxxckM11lk/yYSlzpt1LDINp4abiwEyvyu+3QuLOIAFFn1DF94+Q2mMVJ/NZOa3K6XxUZ0N9WzyV5d+zcBSOgJRPqJr7RzR3D2geDw5nH0ZtN4jChsobrUlWB5d5e5SudoAUdHBVfb963fpPx1Hafbu1cWN14BNhuPocZh2cyW0FvV0V4q/BK4fyN0kWnJQOrhRo5qr8fvWeKlDauUz4uZugratq/EeMyxNqUTgL5FT4UzOazfPRqMV6l02+dKJEPTzQ5lVV/nvlDNbDmcWnriH/O1u03Knsv3KGdIMR9H5Vmb/AeFOM5G9YHJb5/enlm+D3t5SH7b3a63PbCAjphKq1f1/I6V6m29f/GbRPtiW0jk9u9VDSP//n/wPaMm22JYMBAA==';

    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $groups = [
            ['code'=>'CRANES','name'=>'Grúas Telescópicas','display_order'=>10],
            ['code'=>'FORKLIFTS','name'=>'Montacargas','display_order'=>20],
            ['code'=>'TELEHANDLERS','name'=>'Telehandlers','display_order'=>30],
            ['code'=>'MANLIFTS','name'=>'ManLift','display_order'=>40],
            ['code'=>'TRANSPORT','name'=>'Equipos de Transporte','display_order'=>50],
            ['code'=>'EARTHMOVING','name'=>'Maquinaria de Terracería','display_order'=>60],
            ['code'=>'OTHER','name'=>'Otros Servicios','display_order'=>70],
        ];

        foreach ($groups as $group) {
            $existing = $this->db->table('commercial_item_groups')->where('code', $group['code'])->get()->getRowArray();
            $payload = array_merge($group, ['status'=>1, 'modify_user'=>'seeder', 'modify_date'=>$now]);
            if ($existing) {
                $this->db->table('commercial_item_groups')->where('id', $existing['id'])->update($payload);
            } else {
                $payload['entry_user']='seeder';
                $payload['entry_date']=$now;
                $this->db->table('commercial_item_groups')->insert($payload);
            }
        }

        $units = [
            ['code'=>'UNIT','name'=>'Unidad','symbol'=>'Und','abbreviation'=>'Und','display_order'=>10],
            ['code'=>'GLOBAL','name'=>'Suma Global','symbol'=>'SG','abbreviation'=>'SG','display_order'=>20],
            ['code'=>'FREIGHT','name'=>'Flete','symbol'=>'Flt','abbreviation'=>'Flt','display_order'=>30],
            ['code'=>'HOUR','name'=>'Hora','symbol'=>'h','abbreviation'=>'h','display_order'=>40],
            ['code'=>'DAY','name'=>'Día','symbol'=>'día','abbreviation'=>'día','display_order'=>50],
            ['code'=>'WEEK','name'=>'Semana','symbol'=>'sem','abbreviation'=>'sem','display_order'=>60],
            ['code'=>'MONTH','name'=>'Mes','symbol'=>'mes','abbreviation'=>'mes','display_order'=>70],
            ['code'=>'YEAR','name'=>'Año','symbol'=>'año','abbreviation'=>'año','display_order'=>80],
            ['code'=>'SERVICE','name'=>'Servicio','symbol'=>'srv','abbreviation'=>'srv','display_order'=>90],
            ['code'=>'OTHER','name'=>'Otro','symbol'=>'-','abbreviation'=>'-','display_order'=>100],
        ];

        foreach ($units as $unit) {
            $existing = $this->db->table('commercial_units')->where('code', $unit['code'])->get()->getRowArray();
            $payload = array_merge($unit, ['status'=>1, 'modify_user'=>'seeder', 'modify_date'=>$now]);
            if ($existing) {
                $this->db->table('commercial_units')->where('id', $existing['id'])->update($payload);
            } else {
                $payload['entry_user']='seeder';
                $payload['entry_date']=$now;
                $this->db->table('commercial_units')->insert($payload);
            }
        }

        $groupIds = [];
        foreach ($this->db->table('commercial_item_groups')->get()->getResultArray() as $row) {
            $groupIds[$row['code']] = (int) $row['id'];
        }

        $unitIds = [];
        foreach ($this->db->table('commercial_units')->get()->getResultArray() as $row) {
            $unitIds[$row['code']] = (int) $row['id'];
        }

        $compressed = base64_decode(self::DATA, true);
        $json = $compressed === false ? false : gzdecode($compressed);
        $items = $json === false ? null : json_decode($json, true);

        if (! is_array($items)) {
            throw new RuntimeException('No fue posible decodificar el catálogo comercial normalizado.');
        }

        foreach ($items as $item) {
            $payload = [
                'code' => $item['code'],
                'item_type' => 'service',
                'item_group_id' => $groupIds[$item['group']] ?? null,
                'name' => $item['name'],
                'long_description' => $item['long'],
                'default_unit_id' => $unitIds[$item['unit']] ?? null,
                'suggested_price' => $item['price'],
                'allows_price_override' => 1,
                'allows_unit_override' => 1,
                'display_order' => $item['code'] ? (int) substr($item['code'], 5) : 0,
                'source_reference' => 'XLSX row ' . $item['source_row'],
                'normalization_notes' => $item['notes'],
                'status' => 1,
                'modify_user' => 'seeder',
                'modify_date' => $now,
            ];

            $existing = $this->db->table('commercial_items')->where('code', $item['code'])->get()->getRowArray();

            if ($existing) {
                $this->db->table('commercial_items')->where('id', $existing['id'])->update($payload);
                continue;
            }

            $payload['uuid'] = $this->uuidV4();
            $payload['entry_user'] = 'seeder';
            $payload['entry_date'] = $now;
            $this->db->table('commercial_items')->insert($payload);
        }
    }

    private function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
