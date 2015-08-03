library('RUnit')

test.maps_sel <- function(month, maps_sel_R)
{
  if(modtest){
    
    # load matlab karten_sel test for yearmon
    con <- paste(getwd(), '/regmodR/tests/matlab_files/karten_sel_', month, '.mat', sep='')
    
    ## S3 method for class 'default':
    data <- readMat(con, maxLength=NULL, fixNames=TRUE, drop=c("singletonLists"),sparseMatrixClass=c("Matrix", "SparseM", "matrix"), verbose=FALSE) 
    
    #data source variables
    maps_sel_matlab <- data$karten.sel.mat
    matlab_summary <- summary(maps_sel_matlab)
    R_summary <- summary(maps_sel_R) 
    checkEquals(matlab_summary, R_summary)
  }
}

# Tests selected maps from R with Matlab in Function regionalize
test.maps_sel_Regio <- function(maps_sel_R, longitude, latitude, instrumente, lon, lat)
{
  if(modtest){
    
    # load matlab karten_sel test for yearmon
    con <- paste(getwd(), '/regmodR/tests/matlab_files/regionalize()/selectMaps', year, month, '.mat', sep='')
    
    ## S3 method for class 'default':
    data <- readMat(con, maxLength=NULL, fixNames=TRUE, drop=c("singletonLists"),sparseMatrixClass=c("Matrix", "SparseM", "matrix"), verbose=FALSE) 
    #check input data to selectmaps first
    longitude <- matrix(longitude,length(longitude),1)
    checkEquals(longitude, data$longitude)
    latitude <- matrix(latitude,length(latitude),1)
    checkEquals(latitude, data$latitude)
    checkEquals(instrumente, data$instrumente)
    lon <- matrix(lon,1,length(lon))
    checkEquals(lon, data$lon)
    lat <- matrix(lat,1,length(lat))
    checkEquals(lat, data$lat)
    
    #check selectmaps() results
    maps_sel_matlab <- data$karten.sel
    matlab_summary <- summary(maps_sel_matlab)
    R_summary <- summary(maps_sel_R) 
    print(matlab_summary)
    print(R_summary)
    checkEquals(matlab_summary, R_summary)
  }
}

# checks index Data R/MAT equality
test.checkIdxDat <- function(Rdat){
  if(modtest){
    
    con <- paste(getwd(),'/regmodR/tests/matlab_files/idxDat', year, month, '.mat', sep='')
    #con <- paste('CRU_Data/', 'cru_temp_europe_', month, '.mat', sep='')
    
    ## S3 method for class 'default':
    data <- readMat(con, maxLength=NULL, fixNames=TRUE, drop=c("singletonLists"),sparseMatrixClass=c("Matrix", "SparseM", "matrix"), verbose=FALSE)
    
    if(class(Rdat)=="numeric"){
      Rdat <- matrix(Rdat, 1, 3)
    }
    
    checkEquals(Rdat, data$probe.ort)
  }
}

# Test above pre korrels values 
test.preKorrels <- function(idxvaluesR, idx_mapR, idx_preR, maps_sel_incolumnsR, ufaR, uiR){
  if(modtest){
    
    idxvaluesR <<- idxvaluesR
    #   idx_mapR <- idx_map
    #   idx_preR <- idx_pre
    #   maps_sel_incolumnsR <- maps_sel_incolumns
    #   ufaR <- ufaR
    #   uiR <- uiR
    
    con <- paste(getwd(), '/regmodR/tests/matlab_files/regionalize()/preKorrels', year, month, '.mat', sep='')
    
    ## S3 method for class 'default':
    dataM <- readMat(con)
    
    idxvaluesM <<- dataM$indexwerte
    idx_mapM <-  dataM$indizes.karte
    idx_preM <-  dataM$indices.vorh
    maps_sel_incolumnsM <- dataM$karten.sel.inspalten
    ufaM <- dataM$ufa
    
    checkEquals(ufaM, ufaR)
    checkEquals(c(idxvaluesM), c(as.matrix(idxvaluesR[,1:3])))
    checkEquals(idx_mapM, idx_mapR)
    checkEquals(c(idx_preM), c(idx_preR))
    checkEquals(maps_sel_incolumnsM, maps_sel_incolumnsR)
  }
}


test.checkKorrels <- function(korrelsR, korrels_mapR, region_repr_columnsR){
  if(modtest){
    
    con <- paste(getwd(), '/regmodR/tests/matlab_files/korrelMaps', year, month, '.mat', sep='')
    
    ## S3 method for class 'default':
    dataM <- readMat(con)
    korrelsM <- dataM$korrels
    korrellatM <- dataM$korrelationInMap
    repAreaM <- dataM$repArea
    korrelsR <- korrelsR
    
    
    # check area
    checkEquals(repAreaM, region_repr_columnsR)
    # check korrelations
    checkEquals(korrelsM, korrelsR)
    # check korrels in map 
    checkEquals(korrellatM, korrels_mapR)
    
    #   for(i in 1:ddim){
    #     data <- data$korrels[i,,]
    #     Rdat1 <- Rdat[i,,]
    #     print(summary(data))
    #     print(summary(Rdat1))
    #     checkEquals(Rdat1, data)  
    #   }
  }
}

test.unifiedMaps <- function(indiReconR, weightingR){
  if(modtest){
    
    con <- paste(getwd(), '/regmodR/tests/matlab_files/regionalize()/unifiedMaps', year, month, '.mat', sep='')
    ## S3 method for class 'default':
    dataM <- readMat(con)
    
    indiReconM <- dataM$indices.rekon
    weightingM <- dataM$gewichtung
    indiReconR <- indiReconR
    
    checkEquals(weightingM, weightingR)
    checkEquals(indiReconM, indiReconR)
  }
}

test.pca <- function(zscoreR, mapSelColR, regRepColR, hksR, projektionsR, eigenVekR, n){
  if(modtest){
    
    # TODO: rename projections to scores and hks to loadings
    
    zscoreR <- zscoreR
    mapSelColR <- mapSelColR
    regRepColR <- regRepColR
    eigenVekR <- eigenVekR
    
    con <- paste(getwd(), '/regmodR/tests/matlab_files/regionalize()/pca', year, month, '.mat', sep='')
    ## S3 method for class 'default':
    dataM <- readMat(con)
    
    # pca input parameter
    zscoreM <- dataM$zzscore
    mapSelColM <- dataM$karten.sel.inspalten
    regRepColM <- dataM$bereich.repr.inspalten
    # pca results
    # R claculates only the first 100 hks, only the first 20 (default set by n in regionalize) were used
    eigenVekM <- dataM$eigenvek[1:n]
    eigenVekR <- eigenVekR[1:n]
    hksM <- dataM$allehkas.temp[,1:n]
    hksR <- hksR[,1:n]
    projektionsM <- dataM$projektions[,1:n]
    projektionsR <- projektionsR[,1:n]
    
    # Check Input parameters for pca
    # NOTE: if not written [,] -> 'Attributes: < Length mismatch: comparison on 
    # first 1 components > ERROR'???; therefore also summary comparison
    checkEquals(zscoreM[,], zscoreR[,])
    checkEquals(summary(zscoreM), summary(zscoreR))
    checkEquals(mapSelColM, mapSelColR)
    checkEquals(regRepColM, regRepColR)
    
    # test scores
    # NOTE: sign shifting between matlab and R scores & loadings (-> abs())
    # See Note under test loadings and additional testing at the end
    # scores are not further used
    checkEquals(c(abs(projektionsM)), c(abs(projektionsR)))
    
    # test loadings
    # NOTE: sign shifting between matlab and R loadings (-> abs())
    # 'About the sign-shifting; each loading-vector have an "evil twin vector"
    #  pointing in 180 degrees opposite direction in feature space. The loading 
    #  vectors yield scores of opposite signs. Similar to (-1)(+1) =(+1)(-1), 
    #  the solutions are identical. 
    #  [...]
    #  X = loadings x scores + e , and loadings x scores = X(fit)' 
    #  http://stats.stackexchange.com/a/105036
    #
    # Thus loadings(R) x scores(R) = loadings(matlab) x scores(matlab)
    
    checkEquals(c(abs(hksM)), c(abs(hksR)))
    
    # final test if scores and loadings are in its outcome identical
    lxsR <- hksM[1:100,] * projektionsM 
    lxsM <- hksR[1:100,] * projektionsR
    checkEquals(c(lxsM), c(lxsR))
    
    # test eigenvectors
    checkEquals(eigenVekM, eigenVekR)
  }
}

test.mlr <- function(reconR, coefR, ahksR, varR, nR, pcsR, idcReconR, a1R){
  if(modtest){
    
    reconR <- reconR
    coefR <<- coefR
    ahksR <- ahksR
    varR <- varR
    nR <- nR
    pcsR <- pcsR
    idcReconR <-  idcReconR
    a1R <- a1R
    
    # cbind(coefM,coefR)
    con <- paste(getwd(), '/regmodR/tests/matlab_files/regionalize()/mlr', year, month, '.mat', sep='')
    ## S3 method for class 'default':
    dataM <- readMat(con)
    
    reconM <- dataM$rekonstruiert
    coefM <<- dataM$koeffizienten
    ahksM <- dataM$allehkas
    varM <- dataM$erkl.var
    nM <- dataM$n
    pcsM <- dataM$pcs
    idcReconM <- dataM$indices.rekon
    a1M <- dataM$a1
    
    #checkEquals(pcsM, pcsR)
    #checkEquals(coefM, coefR)
    checkEquals(idcReconM, idcReconR)
    checkEquals(a1M[,], a1R)
    checkEquals(reconM, reconR)
    checkEquals(abs(ahksM), abs(ahksR))
    checkEquals(varM[,], varR)
    checkEquals(nM[1], nR)
  }
}

test.checkReconMaps <- function(reconR, meanMapR, stdMapR, a1R){
  if(modtest){
    
    #   reconR <- reconR
    #   meanMapR <- meanMapR
    #   stdMapR <- stdMapR
    #   a1R <- a1R
    
    con <- paste(getwd(), '/regmodR/tests/matlab_files/regionalize()/reconstruct', year, month, '.mat', sep='')
    ## S3 method for class 'default':
    dataM <- readMat(con)
    
    reconM <- dataM$rekonstruiert
    meanMapM <- dataM$karten.mean
    stdMapM <- dataM$karten.std
    a1M <- dataM$a1
    
    checkEquals(a1M[,], a1R)
    checkEquals(meanMapM, meanMapR)
    checkEquals(stdMapM, stdMapR)
    checkEquals(reconM, reconR)
  }
}

test.totalModelOffset <- function(resReconR){
  if(modtest){
    
    resReconR <- resReconR  
    
    con <- paste(getwd(), '/regmodR/tests/matlab_files/oldResM', year, month, '.mat', sep='')
    ## S3 method for class 'default':
    dataM <- readMat(con)
    
    resReconM <- dataM$resRecon
  }
}

